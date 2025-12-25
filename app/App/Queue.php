<?php

namespace TheFramework\App;

use TheFramework\App\Database;

class Queue
{
    /**
     * Kirim Job ke antrian
     * 
     * @param string|object $job Class Job atau Instance Job
     * @param array $data Data jika $job adalah string class name
     * @param string $queue Nama antrian
     */
    public static function push($job, $data = [], $queue = 'default')
    {
        $payload = [
            'job' => is_object($job) ? get_class($job) : $job,
            'data' => is_object($job) ? ($job->data ?? []) : $data,
        ];

        // Jika object, kita serialize manual propertinya agar lebih aman
        if (is_object($job) && method_exists($job, 'getData')) {
            $payload['data'] = $job->getData();
        } elseif (is_object($job)) {
            $payload['data'] = get_object_vars($job);
        }

        $db = Database::getInstance();
        $sql = "INSERT INTO jobs (queue, payload, attempts, available_at, created_at) VALUES (?, ?, 0, ?, ?)";

        $now = time();
        $db->query($sql);
        $db->bind(1, $queue);
        $db->bind(2, json_encode($payload));
        $db->bind(3, $now);
        $db->bind(4, $now);

        $db->execute();

        return $db->lastInsertId();
    }

    /**
     * Ambil Job berikutnya dari antrian (FIFO)
     */
    public static function pop($queue = 'default')
    {
        $db = Database::getInstance();
        $now = time();

        $db->beginTransaction();

        try {
            // Select job tertua yang available
            $sql = "SELECT id, payload, attempts FROM jobs 
                    WHERE queue = ? 
                    AND (reserved_at IS NULL OR reserved_at <= ?) 
                    AND available_at <= ? 
                    ORDER BY id ASC 
                    LIMIT 1 
                    FOR UPDATE";

            $db->query($sql);
            $db->bind(1, $queue);
            $db->bind(2, $now - 90); // 90s timeout
            $db->bind(3, $now);

            $jobRecord = $db->single();

            if ($jobRecord) {
                // Tandai reserved
                $updatedSql = "UPDATE jobs SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?";
                $db->query($updatedSql);
                $db->bind(1, $now);
                $db->bind(2, $jobRecord['id']);
                $db->execute();

                $db->commit();
                return $jobRecord;
            } else {
                $db->commit();
                return null;
            }

        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function delete($id)
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM jobs WHERE id = ?");
        $db->bind(1, $id);
        $db->execute();
    }

    public static function release($id, $delay = 0)
    {
        $db = Database::getInstance();
        $availableAt = time() + $delay;
        $db->query("UPDATE jobs SET reserved_at = NULL, available_at = ? WHERE id = ?");
        $db->bind(1, $availableAt);
        $db->bind(2, $id);
        $db->execute();
    }
}
