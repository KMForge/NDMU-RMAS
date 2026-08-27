<?php

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Notifications\AcademicWorkflowNotification;
use Illuminate\Support\Facades\DB;

class WorkflowNotificationDispatcher
{
    /**
     * @param  array<string, scalar|null>  $routeParameters
     */
    public function send(
        User $recipient,
        string $eventKey,
        string $title,
        string $message,
        string $category,
        string $routeName,
        array $routeParameters,
        string $sourceType,
        int|string $sourceId,
        ?User $actor = null,
        ?string $contextLabel = null,
        ?string $actingAs = null,
        ?string $occurrence = null,
    ): void {
        $logicalKey = hash('sha256', implode('|', [
            $eventKey,
            $sourceType,
            (string) $sourceId,
            (string) $recipient->getKey(),
            $occurrence ?? '',
        ]));

        $dispatch = function () use (
            $recipient,
            $eventKey,
            $title,
            $message,
            $category,
            $logicalKey,
            $routeName,
            $routeParameters,
            $contextLabel,
            $actingAs,
            $actor,
            $sourceType,
            $sourceId,
        ): void {
            $freshRecipient = User::query()->find($recipient->getKey());

            if ($freshRecipient === null || ! $freshRecipient->isActiveAndApproved()) {
                return;
            }

            $alreadySent = $freshRecipient->notifications()
                ->where('data->logical_key', $logicalKey)
                ->exists();

            if ($alreadySent) {
                return;
            }

            $freshRecipient->notify(new AcademicWorkflowNotification(
                eventKey: $eventKey,
                title: $title,
                message: $message,
                category: $category,
                logicalKey: $logicalKey,
                routeName: $routeName,
                routeParameters: $this->safeRouteParameters($routeParameters),
                contextLabel: $contextLabel,
                actingAs: $actingAs,
                actorName: $actor?->name,
                sourceType: $sourceType,
                sourceId: $sourceId,
            ));
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatch);

            return;
        }

        $dispatch();
    }

    /**
     * @param  iterable<User>  $recipients
     * @param  array<string, scalar|null>  $routeParameters
     */
    public function sendToMany(
        iterable $recipients,
        string $eventKey,
        string $title,
        string $message,
        string $category,
        string $routeName,
        array $routeParameters,
        string $sourceType,
        int|string $sourceId,
        ?User $actor = null,
        ?string $contextLabel = null,
        ?string $actingAs = null,
        ?string $occurrence = null,
    ): void {
        collect($recipients)
            ->unique(fn (User $recipient): int|string => $recipient->getKey())
            ->each(fn (User $recipient) => $this->send(
                $recipient,
                $eventKey,
                $title,
                $message,
                $category,
                $routeName,
                $routeParameters,
                $sourceType,
                $sourceId,
                $actor,
                $contextLabel,
                $actingAs,
                $occurrence,
            ));
    }

    /**
     * @param  array<string, scalar|null>  $parameters
     * @return array<string, scalar|null>
     */
    private function safeRouteParameters(array $parameters): array
    {
        return collect($parameters)
            ->filter(fn (mixed $value, mixed $key): bool => is_string($key)
                && preg_match('/^[a-zA-Z0-9_]+$/', $key) === 1
                && (is_scalar($value) || $value === null))
            ->all();
    }
}
