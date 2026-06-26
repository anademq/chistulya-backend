<?php

declare(strict_types=1);

return [
    'route' => [
        'prefix' => 'graphql',
        'controller' => Rebing\GraphQL\GraphQLController::class . '@query',
        'middleware' => ['request.expects_json', 'throttle:graphql'],
        'group_attributes' => [],
    ],

    'default_schema' => 'default',

    'batching' => [
        'enable' => true,
    ],

    'schemas' => [
        'default' => [
            'query' => [
                // Auth & user
                App\GraphQL\Queries\Account\MeQuery::class,
                App\GraphQL\Queries\Account\MySessionsQuery::class,
                // Notifications (child-only)
                App\GraphQL\Queries\Child\NotificationsQuery::class,
                // Categories (public lookup lists)
                App\GraphQL\Queries\DailyTaskCategoriesQuery::class,
                App\GraphQL\Queries\ChallengeCategoriesQuery::class,
                App\GraphQL\Queries\PetItemCategoriesQuery::class,
                // Family
                App\GraphQL\Queries\Parent\MyChildrenQuery::class,
                App\GraphQL\Queries\Parent\ChildProgressSummaryQuery::class,
                App\GraphQL\Queries\Child\MyParentsQuery::class,
                App\GraphQL\Queries\Child\MyChildCabinetQuery::class,
                // Daily tasks (auth — usable by both child and parent with child_id)
                App\GraphQL\Queries\AvailableDailyTasksQuery::class,
                App\GraphQL\Queries\SelectedDailyTasksQuery::class,
                // Challenges
                App\GraphQL\Queries\AvailableChallengesQuery::class,
                App\GraphQL\Queries\SelectedChallengesQuery::class,
                // Achievements
                App\GraphQL\Queries\MyAchievementsQuery::class,
                // Reminders
                App\GraphQL\Queries\MyRemindersQuery::class,
                // Pet shop
                App\GraphQL\Queries\PetCatalogQuery::class,
                App\GraphQL\Queries\MyPetItemsQuery::class,
                // Analytics
                App\GraphQL\Queries\DailyTaskAnalyticsQuery::class,
                App\GraphQL\Queries\ChallengeAnalyticsQuery::class,
                // Subscriptions & payments
                App\GraphQL\Queries\Parent\SubscriptionsQuery::class,
                App\GraphQL\Queries\Account\MyActiveSubscriptionQuery::class,
                App\GraphQL\Queries\Parent\MyPaymentsQuery::class,
            ],
            'mutation' => [
                // Auth (public — no JWT required)
                App\GraphQL\Mutations\Account\RegisterMutation::class,
                App\GraphQL\Mutations\Account\RequestEmailVerificationMutation::class,
                App\GraphQL\Mutations\Account\VerifyEmailMutation::class,
                App\GraphQL\Mutations\Account\LoginMutation::class,
                App\GraphQL\Mutations\Account\RefreshTokenMutation::class,
                App\GraphQL\Mutations\Account\RequestPasswordResetMutation::class,
                App\GraphQL\Mutations\Account\ResetPasswordMutation::class,
                // Auth (JWT required)
                App\GraphQL\Mutations\Account\LogoutMutation::class,
                App\GraphQL\Mutations\Account\UpsertProfileMutation::class,
                App\GraphQL\Mutations\Account\UpdatePasswordMutation::class,
                App\GraphQL\Mutations\Account\RequestMediaUploadMutation::class,
                App\GraphQL\Mutations\Account\ConfirmMediaUploadMutation::class,
                App\GraphQL\Mutations\CreateReminderMutation::class,
                // Notifications (child-only)
                App\GraphQL\Mutations\Child\MarkNotificationsAsReadMutation::class,
                // Family linking
                App\GraphQL\Mutations\Child\CreateChildLinkTokenMutation::class,
                App\GraphQL\Mutations\Parent\LinkChildByTokenMutation::class,
                App\GraphQL\Mutations\Parent\UnlinkChildMutation::class,
                // Daily tasks
                App\GraphQL\Mutations\Child\SelectDailyTaskMutation::class,
                App\GraphQL\Mutations\Child\UnselectDailyTaskMutation::class,
                App\GraphQL\Mutations\Child\CompleteDailyTaskMutation::class,
                App\GraphQL\Mutations\Child\ClaimDailyTaskRewardMutation::class,
                // Challenges
                App\GraphQL\Mutations\Child\SelectChallengeMutation::class,
                App\GraphQL\Mutations\Child\StartChallengeMutation::class,
                App\GraphQL\Mutations\Child\ProgressChallengeMutation::class,
                App\GraphQL\Mutations\Child\ClaimChallengeRewardMutation::class,
                // Daily reward
                App\GraphQL\Mutations\Child\ClaimDailyRewardMutation::class,
                // Achievements
                App\GraphQL\Mutations\Child\ClaimAchievementRewardMutation::class,
                // Reminders
                App\GraphQL\Mutations\Child\CompleteReminderMutation::class,
                App\GraphQL\Mutations\Child\ActivateReminderMutation::class,
                App\GraphQL\Mutations\Child\UpdateReminderMutation::class,
                App\GraphQL\Mutations\Child\DeleteReminderMutation::class,
                // Parent custom content
                App\GraphQL\Mutations\Parent\CreateCustomDailyTaskForChildMutation::class,
                App\GraphQL\Mutations\Parent\UpdateCustomDailyTaskForChildMutation::class,
                App\GraphQL\Mutations\Parent\DeleteCustomDailyTaskForChildMutation::class,
                App\GraphQL\Mutations\Parent\CreateCustomReminderForChildMutation::class,
                // Pet shop
                App\GraphQL\Mutations\Child\PurchasePetItemMutation::class,
                App\GraphQL\Mutations\Child\EquipPetItemMutation::class,
                App\GraphQL\Mutations\Child\UnequipPetItemMutation::class,
                // Subscriptions
                App\GraphQL\Mutations\Parent\SubscribeMutation::class,
                App\GraphQL\Mutations\Parent\RenewSubscriptionMutation::class,
                App\GraphQL\Mutations\Parent\CancelSubscriptionMutation::class,
                App\GraphQL\Mutations\Parent\CreateSubscriptionPaymentMutation::class,
                App\GraphQL\Mutations\Parent\ConfirmSubscriptionPaymentMutation::class,
            ],
            'types' => [],
            'middleware' => null,
            'method' => ['GET', 'POST'],
            'execution_middleware' => null,
            'route_attributes' => [],
        ],

        'admin' => [
            'query' => [
                // Users
                App\GraphQL\Queries\Admin\AdminUsersQuery::class,
                App\GraphQL\Queries\Admin\AdminUserQuery::class,
                // Content management
                App\GraphQL\Queries\Admin\AdminSubscriptionsQuery::class,
                App\GraphQL\Queries\Admin\AdminDailyTasksQuery::class,
                App\GraphQL\Queries\Admin\AdminChallengesQuery::class,
                App\GraphQL\Queries\Admin\AdminAchievementsQuery::class,
                App\GraphQL\Queries\Admin\AdminPetItemsQuery::class,
                App\GraphQL\Queries\Admin\AdminRemindersQuery::class,
                App\GraphQL\Queries\Admin\AdminDailyRewardsQuery::class,
                // Analytics
                App\GraphQL\Queries\Admin\AdminDailyTaskAnalyticsQuery::class,
                App\GraphQL\Queries\Admin\AdminChallengeAnalyticsQuery::class,
            ],
            'mutation' => [
                // User management
                App\GraphQL\Mutations\Admin\AdminCreateUserMutation::class,
                App\GraphQL\Mutations\Admin\AdminUpdateUserMutation::class,
                App\GraphQL\Mutations\Admin\AdminUpsertUserProfileMutation::class,
                App\GraphQL\Mutations\Admin\AdminDeleteUserMutation::class,       // sudo_admin only
                App\GraphQL\Mutations\Admin\AdminForceLogoutMutation::class,
                // Child stats
                App\GraphQL\Mutations\Admin\AdminSetChildExpMutation::class,
                App\GraphQL\Mutations\Admin\AdminAdjustExpMutation::class,
                App\GraphQL\Mutations\Admin\AdminSetChildCoinsMutation::class,
                App\GraphQL\Mutations\Admin\AdminAdjustCoinsMutation::class,
                // Pet items
                App\GraphQL\Mutations\Admin\AdminGrantPetItemToChildMutation::class,
                App\GraphQL\Mutations\Admin\AdminRevokePetItemMutation::class,
                App\GraphQL\Mutations\Admin\AdminClearPetItemsMutation::class,
                // Family
                App\GraphQL\Mutations\Admin\AdminLinkParentChildMutation::class,
                App\GraphQL\Mutations\Admin\AdminUnlinkParentChildMutation::class,
                // Subscriptions
                App\GraphQL\Mutations\Admin\AdminGrantSubscriptionMutation::class,
                App\GraphQL\Mutations\Admin\AdminRevokeSubscriptionMutation::class,
                // Content management
                App\GraphQL\Mutations\Admin\AdminCreateSubscriptionMutation::class,
                App\GraphQL\Mutations\Admin\AdminUpdateSubscriptionMutation::class,
                App\GraphQL\Mutations\Admin\AdminDeleteSubscriptionMutation::class,
                App\GraphQL\Mutations\Admin\AdminCreateDailyTaskMutation::class,
                App\GraphQL\Mutations\Admin\AdminUpdateDailyTaskMutation::class,
                App\GraphQL\Mutations\Admin\AdminDeleteDailyTaskMutation::class,
                App\GraphQL\Mutations\Admin\AdminCreateChallengeMutation::class,
                App\GraphQL\Mutations\Admin\AdminUpdateChallengeMutation::class,
                App\GraphQL\Mutations\Admin\AdminDeleteChallengeMutation::class,
                App\GraphQL\Mutations\Admin\AdminCreateAchievementMutation::class,
                App\GraphQL\Mutations\Admin\AdminUpdateAchievementMutation::class,
                App\GraphQL\Mutations\Admin\AdminDeleteAchievementMutation::class,
                App\GraphQL\Mutations\Admin\AdminCreatePetItemMutation::class,
                App\GraphQL\Mutations\Admin\AdminUpdatePetItemMutation::class,
                App\GraphQL\Mutations\Admin\AdminDeletePetItemMutation::class,
                App\GraphQL\Mutations\Admin\AdminCreateReminderMutation::class,
                App\GraphQL\Mutations\Admin\AdminUpdateReminderMutation::class,
                App\GraphQL\Mutations\Admin\AdminDeleteReminderMutation::class,
                App\GraphQL\Mutations\Admin\AdminCreateDailyRewardMutation::class,
                App\GraphQL\Mutations\Admin\AdminUpdateDailyRewardMutation::class,
                // Media (admin can upload too)
                App\GraphQL\Mutations\Account\RequestMediaUploadMutation::class,
                App\GraphQL\Mutations\Account\ConfirmMediaUploadMutation::class,
            ],
            'types' => [],
            'middleware' => [
                'request.expects_json',
                'throttle:graphql',
            ],
            'method' => ['GET', 'POST'],
            'route_attributes' => [],
        ],
    ],

    // The global types available to all schemas.
    'types' => [
        // ── Shared / error ───────────────────────────────────────────────────
        App\GraphQL\Types\UserErrorType::class,
        App\GraphQL\Types\Errors\ValidationFieldType::class,
        App\GraphQL\Types\Errors\ValidationErrorType::class,
        App\GraphQL\Types\Errors\RateLimitErrorType::class,
        App\GraphQL\Types\Errors\InvalidActionErrorType::class,
        App\GraphQL\Types\Errors\MutationErrorUnionType::class,
        App\GraphQL\Types\MutationStatusType::class,

        // ── User & profile ───────────────────────────────────────────────────
        App\GraphQL\Types\UserType::class,
        App\GraphQL\Types\ProfileType::class,

        // ── Auth ─────────────────────────────────────────────────────────────
        App\GraphQL\Types\SessionType::class,
        App\GraphQL\Types\AccessTokenType::class,
        App\GraphQL\Types\RefreshTokenType::class,
        App\GraphQL\Types\AuthTokensType::class,

        // ── Media ────────────────────────────────────────────────────────────
        App\GraphQL\Types\MediaType::class,

        // ── Game ─────────────────────────────────────────────────────────────
        App\GraphQL\Types\RewardGrantType::class,
        App\GraphQL\Types\WalletType::class,
        App\GraphQL\Types\ExpType::class,

        // ── Family ───────────────────────────────────────────────────────────
        App\GraphQL\Types\FamilyLinkType::class,
        App\GraphQL\Types\ChildLinkTokenType::class,

        // ── Daily tasks ──────────────────────────────────────────────────────
        App\GraphQL\Types\DailyTaskCategoryType::class,
        App\GraphQL\Types\DailyTaskType::class,
        App\GraphQL\Types\DailyTaskAnalyticsPointType::class,
        App\GraphQL\Types\ChildDailyTaskType::class,

        // ── Challenges ───────────────────────────────────────────────────────
        App\GraphQL\Types\ChallengeCategoryType::class,
        App\GraphQL\Types\ChallengeType::class,
        App\GraphQL\Types\ChallengeAnalyticsPointType::class,
        App\GraphQL\Types\ChildChallengeType::class,

        // ── Achievements ─────────────────────────────────────────────────────
        App\GraphQL\Types\AchievementRequirementsType::class,
        App\GraphQL\Types\AchievementRequirementsInput::class,
        App\GraphQL\Types\AchievementType::class,
        App\GraphQL\Types\ChildAchievementType::class,

        // ── Daily rewards ────────────────────────────────────────────────────
        App\GraphQL\Types\DailyRewardType::class,

        // ── Reminders ────────────────────────────────────────────────────────
        App\GraphQL\Types\ReminderType::class,
        App\GraphQL\Types\ChildReminderType::class,

        // ── Pet shop ─────────────────────────────────────────────────────────
        App\GraphQL\Types\PetItemCategoryType::class,
        App\GraphQL\Types\PetItemType::class,
        App\GraphQL\Types\ChildPetItemType::class,

        // ── Subscriptions & payments ─────────────────────────────────────────
        App\GraphQL\Types\SubscriptionType::class,
        App\GraphQL\Types\UserSubscriptionType::class,
        App\GraphQL\Types\PaymentType::class,

        // ── Child cabinet / progress ────────────────────────────────────────
        App\GraphQL\Types\ChildCabinetType::class,
        App\GraphQL\Types\ChildProgressSummaryType::class,

        // ── Payload types (app/GraphQL/Types/Payloads/) ──────────────────────
        App\GraphQL\Types\Payloads\MutationPayloadType::class,
        App\GraphQL\Types\Payloads\AuthPayloadType::class,
        App\GraphQL\Types\Payloads\UpsertProfilePayloadType::class,
        App\GraphQL\Types\Payloads\RequestMediaUploadPayloadType::class,
        App\GraphQL\Types\Payloads\ConfirmMediaUploadPayloadType::class,
        App\GraphQL\Types\Payloads\CreateChildLinkTokenPayloadType::class,
        App\GraphQL\Types\Payloads\LinkChildByTokenPayloadType::class,
        App\GraphQL\Types\Payloads\FamilyLinkPayloadType::class,
        App\GraphQL\Types\Payloads\DailyTaskPayloadType::class,
        App\GraphQL\Types\Payloads\ChildDailyTaskPayloadType::class,
        App\GraphQL\Types\Payloads\ClaimDailyTaskRewardPayloadType::class,
        App\GraphQL\Types\Payloads\ChallengePayloadType::class,
        App\GraphQL\Types\Payloads\ChildChallengePayloadType::class,
        App\GraphQL\Types\Payloads\ClaimChallengeRewardPayloadType::class,
        App\GraphQL\Types\Payloads\AchievementPayloadType::class,
        App\GraphQL\Types\Payloads\ClaimAchievementRewardPayloadType::class,
        App\GraphQL\Types\Payloads\DailyRewardPayloadType::class,
        App\GraphQL\Types\Payloads\ClaimDailyRewardPayloadType::class,
        App\GraphQL\Types\Payloads\ReminderPayloadType::class,
        App\GraphQL\Types\Payloads\PetItemPayloadType::class,
        App\GraphQL\Types\Payloads\PurchasePetItemPayloadType::class,
        App\GraphQL\Types\Payloads\ChildPetItemPayloadType::class,
        App\GraphQL\Types\Payloads\SubscriptionPayloadType::class,
        App\GraphQL\Types\Payloads\PaymentPayloadType::class,
        App\GraphQL\Types\Payloads\UserPayloadType::class,
        App\GraphQL\Types\Payloads\ExpPayloadType::class,
        App\GraphQL\Types\Payloads\WalletPayloadType::class,
    ],

    'error_formatter' => [App\GraphQL\Support\ErrorFormatter::class, 'format'],

    'errors_handler' => [Rebing\GraphQL\GraphQL::class, 'handleErrors'],

    'security' => [
        'query_max_complexity' => null,
        'query_max_depth' => null,
        'disable_introspection' => false,
    ],

    'pagination_type' => Rebing\GraphQL\Support\PaginationType::class,

    'simple_pagination_type' => Rebing\GraphQL\Support\SimplePaginationType::class,

    'cursor_pagination_type' => Rebing\GraphQL\Support\CursorPaginationType::class,

    'defaultFieldResolver' => null,

    'headers' => [],

    'json_encoding_options' => 0,

    'apq' => [
        'enable' => env('GRAPHQL_APQ_ENABLE', false),
        'cache_driver' => env('GRAPHQL_APQ_CACHE_DRIVER', config('cache.default')),
        'cache_prefix' => config('cache.prefix') . ':graphql.apq',
        'cache_ttl' => 300,
    ],

    'execution_middleware' => [
        Rebing\GraphQL\Support\ExecutionMiddleware\ValidateOperationParamsMiddleware::class,
        Rebing\GraphQL\Support\ExecutionMiddleware\AutomaticPersistedQueriesMiddleware::class,
        Rebing\GraphQL\Support\ExecutionMiddleware\AddAuthUserContextValueMiddleware::class,
    ],

    'resolver_middleware_append' => [],
];
