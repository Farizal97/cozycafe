<?php

declare(strict_types=1);

final class LogService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(
        ?int $adminId,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $beforeData,
        ?array $afterData
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO activity_logs
                (admin_id, action, entity_type, entity_id, before_data, after_data)
             VALUES
                (:admin_id, :action, :entity_type, :entity_id, :before_data, :after_data)'
        );
        $statement->execute([
            'admin_id' => $adminId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_data' => $beforeData === null
                ? null
                : json_encode($beforeData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'after_data' => $afterData === null
                ? null
                : json_encode($afterData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
