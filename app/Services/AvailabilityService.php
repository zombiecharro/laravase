<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Appointment;

class AvailabilityService
{
    /**
     * Obtiene los slots disponibles para un usuario en un mes específico.
     *
     * @param int $userId
     * @param array $appointments
     * @param int $year
     * @param int $month
     * @return array
     */

    public function getAvailableSlots($userId, $appointments, $year = null, $month = null)
    {
        // Si no se pasa año o mes, usar el mes actual
        $year = $year ?: now()->year;
        $month = $month ?: now()->month;

        // Calcular inicio y fin del mes
        $startOfMonth = \Carbon\Carbon::create($year, $month, 1, 0, 0, 0)->startOfMonth();
        $endOfMonth = (clone $startOfMonth)->endOfMonth();

        // 1. Obtener los shifts del usuario (sin filtrar por fecha, porque solo tienen hora)
        $shifts = Shift::where('user_id', $userId)->get()->toArray();

        // 2. Procesar los shifts negativos y restarlos de los positivos
        $availableRanges = $this->processNegativeShifts($shifts);

        // 3. Generar los slots para cada día del mes usando los rangos de hora
        $slots = [];
        $slotDuration = 30; // minutos

        // Recorrer cada día del mes
        for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
            // Para cada rango disponible (ej: 09:00:00 a 17:00:00)
            foreach ($availableRanges as $range) {
                // Combinar la fecha del día con la hora del shift
                $startDateTime = $date->copy()->setTimeFromTimeString($range['start']);
                $endDateTime = $date->copy()->setTimeFromTimeString($range['end']);
                // Generar slots para ese día y rango
                $slots = array_merge($slots, $this->generateSlots([[
                    'start' => $startDateTime->format('Y-m-d H:i:s'),
                    'end' => $endDateTime->format('Y-m-d H:i:s'),
                ]], $slotDuration));
            }
        }

        // 4. Filtrar citas SOLO del mes
        $filteredAppointments = array_filter($appointments, function($appt) use ($startOfMonth, $endOfMonth) {
            return (
                ($appt['start_time'] >= $startOfMonth->format('Y-m-d 00:00:00') && $appt['start_time'] <= $endOfMonth->format('Y-m-d 23:59:59')) ||
                ($appt['end_time'] >= $startOfMonth->format('Y-m-d 00:00:00') && $appt['end_time'] <= $endOfMonth->format('Y-m-d 23:59:59'))
            );
        });

        // 5. Eliminar los slots que ya están ocupados por citas
        $slots = $this->removeOccupiedSlots($slots, $filteredAppointments);

        // 6. Agrupar los slots por día (formato 'Y-m-d')
        $grouped = [];
        foreach ($slots as $slot) {
            $date = substr($slot['start'], 0, 10); // 'Y-m-d'
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = $slot;
        }

        return $grouped;
    }

    /**
     * Genera los slots a partir de los rangos disponibles, limitando a un rango de fechas.
     */
    private function generateSlotsForRange(array $ranges, int $slotDuration, $startLimit, $endLimit): array
    {
        $slots = [];
        foreach ($ranges as $range) {
            $start = \Carbon\Carbon::parse($range['start']);
            $end = \Carbon\Carbon::parse($range['end']);
            // Limitar el rango al mes solicitado
            if ($start < $startLimit) $start = $startLimit->copy();
            if ($end > $endLimit) $end = $endLimit->copy();
            while ($start->copy()->addMinutes($slotDuration) <= $end) {
                $slotEnd = $start->copy()->addMinutes($slotDuration);
                $slots[] = [
                    'start' => $start->format('Y-m-d H:i:s'),
                    'end' => $slotEnd->format('Y-m-d H:i:s'),
                ];
                $start = $slotEnd;
            }
        }
        return $slots;
    }

    private function processNegativeShifts($shifts)
    {
            // Separar shifts positivos y negativos
            $positives = array_filter($shifts, function ($shift) {
                return empty($shift['reverse']);
            });
            $negatives = array_filter($shifts, function ($shift) {
                return !empty($shift['reverse']);
            });

            // Convertir a arrays simples para manipulación
            $available = [];
            foreach ($positives as $pos) {
                $available[] = [
                    'start' => $pos['start_time'],
                    'end' => $pos['end_time'],
                ];
            }

            // Restar los rangos negativos de los positivos
            foreach ($negatives as $neg) {
                $negStart = $neg['start_time'];
                $negEnd = $neg['end_time'];
                $newAvailable = [];
                foreach ($available as $range) {
                    // Si no se solapan, mantener el rango
                    if ($negEnd <= $range['start'] || $negStart >= $range['end']) {
                        $newAvailable[] = $range;
                    } else {
                        // Si se solapan, cortar el rango positivo
                        if ($negStart > $range['start']) {
                            $newAvailable[] = [
                                'start' => $range['start'],
                                'end' => $negStart,
                            ];
                        }
                        if ($negEnd < $range['end']) {
                            $newAvailable[] = [
                                'start' => $negEnd,
                                'end' => $range['end'],
                            ];
                        }
                    }
                }
                $available = $newAvailable;
            }

            // Devuelve los rangos disponibles finales
            return $available;
    }
    
        /**
         * Genera los slots a partir de los rangos disponibles.
         *
         * @param array $ranges
         * @param int $slotDuration
         * @return array
         */
        private function generateSlots(array $ranges, int $slotDuration): array
        {
            // Genera slots de duración fija a partir de los rangos
            $slots = [];
            foreach ($ranges as $range) {
                $start = \Carbon\Carbon::parse($range['start']);
                $end = \Carbon\Carbon::parse($range['end']);
                while ($start->copy()->addMinutes($slotDuration) <= $end) {
                    $slotEnd = $start->copy()->addMinutes($slotDuration);
                    $slots[] = [
                        'start' => $start->format('Y-m-d H:i:s'),
                        'end' => $slotEnd->format('Y-m-d H:i:s'),
                    ];
                    $start = $slotEnd;
                }
            }
            return $slots;
        }

        /**
         * Elimina los slots que ya están ocupados por citas.
         *
         * @param array $slots
         * @param array $appointments
         * @return array
         */
        private function removeOccupiedSlots(array $slots, array $appointments): array
        {
            // Filtra los slots que se solapan con alguna cita
            $availableSlots = [];
            foreach ($slots as $slot) {
                $slotStart = \Carbon\Carbon::parse($slot['start']);
                $slotEnd = \Carbon\Carbon::parse($slot['end']);
                $overlap = false;
                foreach ($appointments as $appt) {
                    $apptStart = \Carbon\Carbon::parse($appt['start_time']);
                    $apptEnd = \Carbon\Carbon::parse($appt['end_time']);
                    // Si hay solapamiento
                    if ($slotStart < $apptEnd && $slotEnd > $apptStart) {
                        $overlap = true;
                        break;
                    }
                }
                if (!$overlap) {
                    $availableSlots[] = $slot;
                }
            }
            return $availableSlots;
        }
}