<?php

namespace App\DTO;

use App\Enums\PlayerTier;

class ParticipantTierDTO
{
    public function __construct(
        public readonly int $mini_participant_id,
        public readonly PlayerTier $tier,
    ) {}

    public static function fromArray(array $data): self
    {
        $tier = $data['tier'] ?? null;
        if (is_string($tier)) {
            $tier = PlayerTier::from($tier);
        } elseif ($tier === null) {
            // Default to Green when tier is not provided
            $tier = PlayerTier::Green;
        }

        return new self(
            mini_participant_id: $data['mini_participant_id'],
            tier: $tier,
        );
    }

    public function toArray(): array
    {
        return [
            'mini_participant_id' => $this->mini_participant_id,
            'tier' => $this->tier->value,
        ];
    }
}

class MatchSuggestionSettingsDTO
{
    public function __construct(
        public readonly bool $fair_play = true,
        public readonly bool $balance_team = true,
        public readonly bool $prefer_high_tier_match = true,
        public readonly bool $prevent_three_consecutive = true,
        public readonly bool $organizer_as_backup = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            fair_play: $data['fair_play'] ?? true,
            balance_team: $data['balance_team'] ?? true,
            prefer_high_tier_match: $data['prefer_high_tier_match'] ?? true,
            prevent_three_consecutive: $data['prevent_three_consecutive'] ?? true,
            organizer_as_backup: $data['organizer_as_backup'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'fair_play' => $this->fair_play,
            'balance_team' => $this->balance_team,
            'prefer_high_tier_match' => $this->prefer_high_tier_match,
            'prevent_three_consecutive' => $this->prevent_three_consecutive,
            'organizer_as_backup' => $this->organizer_as_backup,
        ];
    }
}

/**
 * DTO for a fixed player pair (for pairing constraint).
 * Always uses user_id - backend resolves guest flag via mini_participants table when needed.
 */
class FixedPairDTO
{
    public function __construct(
        public readonly int $player1_id,
        public readonly int $player2_id,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            player1_id: $data['player1_id'],
            player2_id: $data['player2_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'player1_id' => $this->player1_id,
            'player2_id' => $this->player2_id,
        ];
    }

    /**
     * Check if a user is part of this pair.
     * Always uses user_id.
     */
    public function hasPlayer(int $userId): bool
    {
        return (int) $this->player1_id === $userId || (int) $this->player2_id === $userId;
    }

    /**
     * Get the partner of a user in this pair.
     * Returns partner's user_id.
     */
    public function getPartnerId(int $userId): ?int
    {
        if ((int) $this->player1_id === $userId) {
            return (int) $this->player2_id;
        }
        if ((int) $this->player2_id === $userId) {
            return (int) $this->player1_id;
        }
        return null;
    }
}

class MatchSuggestionRequestDTO
{
    /**
     * @param ParticipantTierDTO[] $participants
     * @param FixedPairDTO[] $fixed_pairs
     */
    public function __construct(
        public readonly int $mini_tournament_id,
        public readonly array $participants,
        public readonly MatchSuggestionSettingsDTO $settings,
        public readonly ?int $seed = null,
        public readonly ?array $exclude_player_ids = null,
        /** @deprecated Use anchor_user_id instead */
        public readonly ?int $anchor_participant_id = null,
        /** ID of the player who must be in the selected match (user_id or mini_participant_id for guests) */
        public readonly ?int $anchor_user_id = null,
        /** Fixed player pairs - these players must be on the same team */
        public readonly array $fixed_pairs = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $participants = [];
        foreach ($data['participants'] ?? [] as $p) {
            $participants[] = ParticipantTierDTO::fromArray($p);
        }

        $fixedPairs = [];
        foreach ($data['fixed_pairs'] ?? [] as $pair) {
            $fixedPairs[] = FixedPairDTO::fromArray($pair);
        }

        if (!isset($data['mini_tournament_id'])) {
            throw new \InvalidArgumentException('mini_tournament_id là bắt buộc.');
        }

        return new self(
            mini_tournament_id: $data['mini_tournament_id'],
            participants: $participants,
            settings: MatchSuggestionSettingsDTO::fromArray($data['settings'] ?? []),
            seed: $data['seed'] ?? null,
            exclude_player_ids: $data['exclude_player_ids'] ?? null,
            anchor_participant_id: $data['anchor_participant_id'] ?? null,
            anchor_user_id: $data['anchor_user_id'] ?? null,
            fixed_pairs: $fixedPairs,
        );
    }

    public function toArray(): array
    {
        return [
            'mini_tournament_id' => $this->mini_tournament_id,
            'participants' => array_map(fn($p) => $p->toArray(), $this->participants),
            'settings' => $this->settings->toArray(),
            'seed' => $this->seed,
            'exclude_player_ids' => $this->exclude_player_ids,
            'anchor_participant_id' => $this->anchor_participant_id,
            'anchor_user_id' => $this->anchor_user_id,
            'fixed_pairs' => array_map(fn($p) => $p->toArray(), $this->fixed_pairs),
        ];
    }

    /**
     * Return a NEW MatchSuggestionRequestDTO with fixed_pairs normalized so that
     * both player IDs are user_ids.
     *
     * The frontend stores player1_id / player2_id as mini_participant_id, but
     * FixedPairDTO::hasPlayer() compares them to user_id. When the payload
     * value is NOT a known user_id, we try to resolve it as a
     * mini_participant_id by consulting the provided map.
     *
     * @param array $miniParticipantIdToUserId mini_participant_id (int) => user_id (int|null)
     * @return self New instance with resolved fixed_pairs (unchanged when empty)
     */
    public function normalizeToUserIds(array $miniParticipantIdToUserId): self
    {
        if (empty($this->fixed_pairs)) {
            return $this;
        }

        $normalized = [];
        foreach ($this->fixed_pairs as $pair) {
            $uid1 = self::resolveToUserId($pair->player1_id, $miniParticipantIdToUserId);
            $uid2 = self::resolveToUserId($pair->player2_id, $miniParticipantIdToUserId);

            // Skip pairs where either member cannot be resolved.
            // Creating a pair with player_id=0 would cause hasPlayer() to silently fail
            // because (0 === $userId) is always false, breaking the constraint.
            if ($uid1 === null || $uid2 === null) {
                continue;
            }

            $normalized[] = new FixedPairDTO(
                player1_id: $uid1,
                player2_id: $uid2,
            );
        }

        return new self(
            mini_tournament_id: $this->mini_tournament_id,
            participants: $this->participants,
            settings: $this->settings,
            seed: $this->seed,
            exclude_player_ids: $this->exclude_player_ids,
            anchor_participant_id: $this->anchor_participant_id,
            anchor_user_id: $this->anchor_user_id,
            fixed_pairs: $normalized,
        );
    }

    /**
     * Try to resolve a numeric ID to a user_id.
     * - If $id exists as a mini_participant_id key in the map, return its user_id.
     * - If $id exists as a user_id value in the map, return it unchanged.
     * - Otherwise returns null (unresolvable).
     */
    private static function resolveToUserId(int $id, array $map): ?int
    {
        // Case 1: $id is a mini_participant_id key → resolve to user_id
        if (array_key_exists($id, $map) && $map[$id] !== null) {
            return (int) $map[$id];
        }

        // Case 2: $id is already a user_id value → return as-is
        if (in_array($id, $map, true)) {
            return $id;
        }

        // Case 3: cannot resolve
        return null;
    }
}
