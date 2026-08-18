<?php

declare(strict_types=1);
use App\GraphQL\Mutations\Account\ConfirmMediaUploadMutation;
use App\GraphQL\Mutations\Account\LoginMutation;
use App\GraphQL\Mutations\Account\LogoutMutation;
use App\GraphQL\Mutations\Account\RefreshTokenMutation;
use App\GraphQL\Mutations\Account\RegisterMutation;
use App\GraphQL\Mutations\Account\RequestEmailVerificationMutation;
use App\GraphQL\Mutations\Account\RequestMediaUploadMutation;
use App\GraphQL\Mutations\Account\RequestPasswordResetMutation;
use App\GraphQL\Mutations\Account\ResetPasswordMutation;
use App\GraphQL\Mutations\Account\UpdatePasswordMutation;
use App\GraphQL\Mutations\Account\UpsertProfileMutation;
use App\GraphQL\Mutations\Account\VerifyEmailMutation;
use App\GraphQL\Mutations\Admin\AdminAdjustCoinsMutation;
use App\GraphQL\Mutations\Admin\AdminAdjustExpMutation;
use App\GraphQL\Mutations\Admin\AdminClearPetItemsMutation;
use App\GraphQL\Mutations\Admin\AdminCreateAchievementMutation;
use App\GraphQL\Mutations\Admin\AdminCreateChallengeMutation;
use App\GraphQL\Mutations\Admin\AdminCreateDailyRewardMutation;
use App\GraphQL\Mutations\Admin\AdminCreateDailyTaskMutation;
use App\GraphQL\Mutations\Admin\AdminCreatePetItemMutation;
use App\GraphQL\Mutations\Admin\AdminCreateReminderMutation;
use App\GraphQL\Mutations\Admin\AdminCreateSubscriptionMutation;
use App\GraphQL\Mutations\Admin\AdminCreateUserMutation;
use App\GraphQL\Mutations\Admin\AdminDeleteAchievementMutation;
use App\GraphQL\Mutations\Admin\AdminDeleteChallengeMutation;
use App\GraphQL\Mutations\Admin\AdminDeleteDailyTaskMutation;
use App\GraphQL\Mutations\Admin\AdminDeletePetItemMutation;
use App\GraphQL\Mutations\Admin\AdminDeleteReminderMutation;
use App\GraphQL\Mutations\Admin\AdminDeleteSubscriptionMutation;
use App\GraphQL\Mutations\Admin\AdminDeleteUserMutation;
use App\GraphQL\Mutations\Admin\AdminForceLogoutMutation;
use App\GraphQL\Mutations\Admin\AdminGrantPetItemToChildMutation;
use App\GraphQL\Mutations\Admin\AdminGrantSubscriptionMutation;
use App\GraphQL\Mutations\Admin\AdminLinkParentChildMutation;
use App\GraphQL\Mutations\Admin\AdminRevokePetItemMutation;
use App\GraphQL\Mutations\Admin\AdminRevokeSubscriptionMutation;
use App\GraphQL\Mutations\Admin\AdminSetChildCoinsMutation;
use App\GraphQL\Mutations\Admin\AdminSetChildExpMutation;
use App\GraphQL\Mutations\Admin\AdminUnlinkParentChildMutation;
use App\GraphQL\Mutations\Admin\AdminUpdateAchievementMutation;
use App\GraphQL\Mutations\Admin\AdminUpdateChallengeMutation;
use App\GraphQL\Mutations\Admin\AdminUpdateDailyRewardMutation;
use App\GraphQL\Mutations\Admin\AdminUpdateDailyTaskMutation;
use App\GraphQL\Mutations\Admin\AdminUpdatePetItemMutation;
use App\GraphQL\Mutations\Admin\AdminUpdateReminderMutation;
use App\GraphQL\Mutations\Admin\AdminUpdateSubscriptionMutation;
use App\GraphQL\Mutations\Admin\AdminUpdateUserMutation;
use App\GraphQL\Mutations\Admin\AdminUpsertUserProfileMutation;
use App\GraphQL\Mutations\Admin\Category\AdminCreateChallengeCategoryMutation;
use App\GraphQL\Mutations\Admin\Category\AdminCreateDailyTaskCategoryMutation;
use App\GraphQL\Mutations\Admin\Category\AdminCreatePetItemCategoryMutation;
use App\GraphQL\Mutations\Admin\Category\AdminDeleteChallengeCategoryMutation;
use App\GraphQL\Mutations\Admin\Category\AdminDeleteDailyTaskCategoryMutation;
use App\GraphQL\Mutations\Admin\Category\AdminDeletePetItemCategoryMutation;
use App\GraphQL\Mutations\Admin\Category\AdminUpdateChallengeCategoryMutation;
use App\GraphQL\Mutations\Admin\Category\AdminUpdateDailyTaskCategoryMutation;
use App\GraphQL\Mutations\Admin\Category\AdminUpdatePetItemCategoryMutation;
use App\GraphQL\Mutations\Child\ActivateReminderMutation;
use App\GraphQL\Mutations\Child\ClaimAchievementRewardMutation;
use App\GraphQL\Mutations\Child\ClaimChallengeRewardMutation;
use App\GraphQL\Mutations\Child\ClaimDailyRewardMutation;
use App\GraphQL\Mutations\Child\ClaimDailyTaskRewardMutation;
use App\GraphQL\Mutations\Child\CompleteDailyTaskMutation;
use App\GraphQL\Mutations\Child\CompleteReminderMutation;
use App\GraphQL\Mutations\Child\CreateChildLinkTokenMutation;
use App\GraphQL\Mutations\Child\DeleteReminderMutation;
use App\GraphQL\Mutations\Child\EquipPetItemMutation;
use App\GraphQL\Mutations\Child\MarkNotificationsAsReadMutation;
use App\GraphQL\Mutations\Child\ProgressChallengeMutation;
use App\GraphQL\Mutations\Child\PurchasePetItemMutation;
use App\GraphQL\Mutations\Child\SelectChallengeMutation;
use App\GraphQL\Mutations\Child\SelectDailyTaskMutation;
use App\GraphQL\Mutations\Child\StartChallengeMutation;
use App\GraphQL\Mutations\Child\UnequipPetItemMutation;
use App\GraphQL\Mutations\Child\UnselectDailyTaskMutation;
use App\GraphQL\Mutations\Child\UpdateReminderMutation;
use App\GraphQL\Mutations\CreateReminderMutation;
use App\GraphQL\Mutations\Parent\CancelSubscriptionMutation;
use App\GraphQL\Mutations\Parent\ConfirmSubscriptionPaymentMutation;
use App\GraphQL\Mutations\Parent\CreateCustomDailyTaskForChildMutation;
use App\GraphQL\Mutations\Parent\CreateCustomReminderForChildMutation;
use App\GraphQL\Mutations\Parent\CreateSubscriptionPaymentMutation;
use App\GraphQL\Mutations\Parent\DeleteCustomDailyTaskForChildMutation;
use App\GraphQL\Mutations\Parent\LinkChildByTokenMutation;
use App\GraphQL\Mutations\Parent\RenewSubscriptionMutation;
use App\GraphQL\Mutations\Parent\SubscribeMutation;
use App\GraphQL\Mutations\Parent\UnlinkChildMutation;
use App\GraphQL\Mutations\Parent\UpdateCustomDailyTaskForChildMutation;
use App\GraphQL\Queries\Account\MeQuery;
use App\GraphQL\Queries\Account\MyActiveSubscriptionQuery;
use App\GraphQL\Queries\Account\MySessionsQuery;
use App\GraphQL\Queries\Admin\AdminAchievementsQuery;
use App\GraphQL\Queries\Admin\AdminChallengeAnalyticsQuery;
use App\GraphQL\Queries\Admin\AdminChallengesQuery;
use App\GraphQL\Queries\Admin\AdminDailyRewardsQuery;
use App\GraphQL\Queries\Admin\AdminDailyTaskAnalyticsQuery;
use App\GraphQL\Queries\Admin\AdminDailyTasksQuery;
use App\GraphQL\Queries\Admin\AdminPetItemsQuery;
use App\GraphQL\Queries\Admin\AdminRemindersQuery;
use App\GraphQL\Queries\Admin\AdminSubscriptionsQuery;
use App\GraphQL\Queries\Admin\AdminUserQuery;
use App\GraphQL\Queries\Admin\AdminUsersQuery;
use App\GraphQL\Queries\AvailableChallengesQuery;
use App\GraphQL\Queries\AvailableDailyTasksQuery;
use App\GraphQL\Queries\ChallengeAnalyticsQuery;
use App\GraphQL\Queries\ChallengeCategoriesQuery;
use App\GraphQL\Queries\Child\MyChildCabinetQuery;
use App\GraphQL\Queries\Child\MyParentsQuery;
use App\GraphQL\Queries\Child\NotificationsQuery;
use App\GraphQL\Queries\DailyTaskAnalyticsQuery;
use App\GraphQL\Queries\DailyTaskCategoriesQuery;
use App\GraphQL\Queries\MyAchievementsQuery;
use App\GraphQL\Queries\MyPetItemsQuery;
use App\GraphQL\Queries\MyRemindersQuery;
use App\GraphQL\Queries\Parent\ChildProgressSummaryQuery;
use App\GraphQL\Queries\Parent\MyChildrenQuery;
use App\GraphQL\Queries\Parent\MyPaymentsQuery;
use App\GraphQL\Queries\Parent\SubscriptionsQuery;
use App\GraphQL\Queries\PetCatalogQuery;
use App\GraphQL\Queries\PetItemCategoriesQuery;
use App\GraphQL\Queries\SelectedChallengesQuery;
use App\GraphQL\Queries\SelectedDailyTasksQuery;
use App\GraphQL\Support\ErrorFormatter;
use App\GraphQL\Types\AccessTokenType;
use App\GraphQL\Types\AchievementRequirementsInput;
use App\GraphQL\Types\AchievementRequirementsType;
use App\GraphQL\Types\AchievementType;
use App\GraphQL\Types\AuthTokensType;
use App\GraphQL\Types\ChallengeAnalyticsPointType;
use App\GraphQL\Types\ChallengeCategoryType;
use App\GraphQL\Types\ChallengeType;
use App\GraphQL\Types\ChildAchievementType;
use App\GraphQL\Types\ChildCabinetType;
use App\GraphQL\Types\ChildChallengeType;
use App\GraphQL\Types\ChildDailyTaskType;
use App\GraphQL\Types\ChildLinkTokenType;
use App\GraphQL\Types\ChildPetItemType;
use App\GraphQL\Types\ChildProgressSummaryType;
use App\GraphQL\Types\ChildReminderType;
use App\GraphQL\Types\DailyRewardType;
use App\GraphQL\Types\DailyTaskAnalyticsPointType;
use App\GraphQL\Types\DailyTaskCategoryType;
use App\GraphQL\Types\DailyTaskType;
use App\GraphQL\Types\Errors\InvalidActionErrorType;
use App\GraphQL\Types\Errors\MutationErrorUnionType;
use App\GraphQL\Types\Errors\RateLimitErrorType;
use App\GraphQL\Types\Errors\ValidationErrorType;
use App\GraphQL\Types\Errors\ValidationFieldType;
use App\GraphQL\Types\ExpType;
use App\GraphQL\Types\FamilyLinkType;
use App\GraphQL\Types\MediaType;
use App\GraphQL\Types\MutationStatusType;
use App\GraphQL\Types\Payloads\AchievementPayloadType;
use App\GraphQL\Types\Payloads\AuthPayloadType;
use App\GraphQL\Types\Payloads\ChallengeCategoryPayloadType;
use App\GraphQL\Types\Payloads\ChallengePayloadType;
use App\GraphQL\Types\Payloads\ChildChallengePayloadType;
use App\GraphQL\Types\Payloads\ChildDailyTaskPayloadType;
use App\GraphQL\Types\Payloads\ChildPetItemPayloadType;
use App\GraphQL\Types\Payloads\ClaimAchievementRewardPayloadType;
use App\GraphQL\Types\Payloads\ClaimChallengeRewardPayloadType;
use App\GraphQL\Types\Payloads\ClaimDailyRewardPayloadType;
use App\GraphQL\Types\Payloads\ClaimDailyTaskRewardPayloadType;
use App\GraphQL\Types\Payloads\ConfirmMediaUploadPayloadType;
use App\GraphQL\Types\Payloads\CreateChildLinkTokenPayloadType;
use App\GraphQL\Types\Payloads\DailyRewardPayloadType;
use App\GraphQL\Types\Payloads\DailyTaskCategoryPayloadType;
use App\GraphQL\Types\Payloads\DailyTaskPayloadType;
use App\GraphQL\Types\Payloads\ExpPayloadType;
use App\GraphQL\Types\Payloads\FamilyLinkPayloadType;
use App\GraphQL\Types\Payloads\LinkChildByTokenPayloadType;
use App\GraphQL\Types\Payloads\MutationPayloadType;
use App\GraphQL\Types\Payloads\PaymentPayloadType;
use App\GraphQL\Types\Payloads\PetItemCategoryPayloadType;
use App\GraphQL\Types\Payloads\PetItemPayloadType;
use App\GraphQL\Types\Payloads\PurchasePetItemPayloadType;
use App\GraphQL\Types\Payloads\ReminderPayloadType;
use App\GraphQL\Types\Payloads\RequestMediaUploadPayloadType;
use App\GraphQL\Types\Payloads\SubscriptionPayloadType;
use App\GraphQL\Types\Payloads\UpsertProfilePayloadType;
use App\GraphQL\Types\Payloads\UserPayloadType;
use App\GraphQL\Types\Payloads\WalletPayloadType;
use App\GraphQL\Types\PaymentType;
use App\GraphQL\Types\PetItemCategoryType;
use App\GraphQL\Types\PetItemType;
use App\GraphQL\Types\ProfileType;
use App\GraphQL\Types\RefreshTokenType;
use App\GraphQL\Types\ReminderType;
use App\GraphQL\Types\RewardGrantType;
use App\GraphQL\Types\SessionType;
use App\GraphQL\Types\SubscriptionType;
use App\GraphQL\Types\UserErrorType;
use App\GraphQL\Types\UserSubscriptionType;
use App\GraphQL\Types\UserType;
use App\GraphQL\Types\WalletType;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\GraphQLController;
use Rebing\GraphQL\Support\CursorPaginationType;
use Rebing\GraphQL\Support\ExecutionMiddleware\AddAuthUserContextValueMiddleware;
use Rebing\GraphQL\Support\ExecutionMiddleware\AutomaticPersistedQueriesMiddleware;
use Rebing\GraphQL\Support\ExecutionMiddleware\ValidateOperationParamsMiddleware;
use Rebing\GraphQL\Support\PaginationType;
use Rebing\GraphQL\Support\SimplePaginationType;

return [
    'route' => [
        'prefix' => 'graphql',
        'controller' => GraphQLController::class.'@query',
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
                MeQuery::class,
                MySessionsQuery::class,
                // Notifications (child-only)
                NotificationsQuery::class,
                // Categories (public lookup lists)
                DailyTaskCategoriesQuery::class,
                ChallengeCategoriesQuery::class,
                PetItemCategoriesQuery::class,
                // Family
                MyChildrenQuery::class,
                ChildProgressSummaryQuery::class,
                MyParentsQuery::class,
                MyChildCabinetQuery::class,
                // Daily tasks (auth — usable by both child and parent with child_id)
                AvailableDailyTasksQuery::class,
                SelectedDailyTasksQuery::class,
                // Challenges
                AvailableChallengesQuery::class,
                SelectedChallengesQuery::class,
                // Achievements
                MyAchievementsQuery::class,
                // Reminders
                MyRemindersQuery::class,
                // Pet shop
                PetCatalogQuery::class,
                MyPetItemsQuery::class,
                // Analytics
                DailyTaskAnalyticsQuery::class,
                ChallengeAnalyticsQuery::class,
                // Subscriptions & payments
                SubscriptionsQuery::class,
                MyActiveSubscriptionQuery::class,
                MyPaymentsQuery::class,
            ],
            'mutation' => [
                // Auth (public — no JWT required)
                RegisterMutation::class,
                RequestEmailVerificationMutation::class,
                VerifyEmailMutation::class,
                LoginMutation::class,
                RefreshTokenMutation::class,
                RequestPasswordResetMutation::class,
                ResetPasswordMutation::class,
                // Auth (JWT required)
                LogoutMutation::class,
                UpsertProfileMutation::class,
                UpdatePasswordMutation::class,
                RequestMediaUploadMutation::class,
                ConfirmMediaUploadMutation::class,
                CreateReminderMutation::class,
                // Notifications (child-only)
                MarkNotificationsAsReadMutation::class,
                // Family linking
                CreateChildLinkTokenMutation::class,
                LinkChildByTokenMutation::class,
                UnlinkChildMutation::class,
                // Daily tasks
                SelectDailyTaskMutation::class,
                UnselectDailyTaskMutation::class,
                CompleteDailyTaskMutation::class,
                ClaimDailyTaskRewardMutation::class,
                // Challenges
                SelectChallengeMutation::class,
                StartChallengeMutation::class,
                ProgressChallengeMutation::class,
                ClaimChallengeRewardMutation::class,
                // Daily reward
                ClaimDailyRewardMutation::class,
                // Achievements
                ClaimAchievementRewardMutation::class,
                // Reminders
                CompleteReminderMutation::class,
                ActivateReminderMutation::class,
                UpdateReminderMutation::class,
                DeleteReminderMutation::class,
                // Parent custom content
                CreateCustomDailyTaskForChildMutation::class,
                UpdateCustomDailyTaskForChildMutation::class,
                DeleteCustomDailyTaskForChildMutation::class,
                CreateCustomReminderForChildMutation::class,
                // Pet shop
                PurchasePetItemMutation::class,
                EquipPetItemMutation::class,
                UnequipPetItemMutation::class,
                // Subscriptions
                SubscribeMutation::class,
                RenewSubscriptionMutation::class,
                CancelSubscriptionMutation::class,
                CreateSubscriptionPaymentMutation::class,
                ConfirmSubscriptionPaymentMutation::class,
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
                AdminUsersQuery::class,
                AdminUserQuery::class,
                // Content management
                AdminSubscriptionsQuery::class,
                AdminDailyTasksQuery::class,
                AdminChallengesQuery::class,
                AdminAchievementsQuery::class,
                AdminPetItemsQuery::class,
                AdminRemindersQuery::class,
                AdminDailyRewardsQuery::class,
                // Analytics
                AdminDailyTaskAnalyticsQuery::class,
                AdminChallengeAnalyticsQuery::class,
            ],
            'mutation' => [
                // User management
                AdminCreateUserMutation::class,
                AdminUpdateUserMutation::class,
                AdminUpsertUserProfileMutation::class,
                AdminDeleteUserMutation::class,       // sudo_admin only
                AdminForceLogoutMutation::class,
                // Child stats
                AdminSetChildExpMutation::class,
                AdminAdjustExpMutation::class,
                AdminSetChildCoinsMutation::class,
                AdminAdjustCoinsMutation::class,
                // Pet items
                AdminGrantPetItemToChildMutation::class,
                AdminRevokePetItemMutation::class,
                AdminClearPetItemsMutation::class,
                // Family
                AdminLinkParentChildMutation::class,
                AdminUnlinkParentChildMutation::class,
                // Subscriptions
                AdminGrantSubscriptionMutation::class,
                AdminRevokeSubscriptionMutation::class,
                // Content management
                AdminCreateSubscriptionMutation::class,
                AdminUpdateSubscriptionMutation::class,
                AdminDeleteSubscriptionMutation::class,
                AdminCreateDailyTaskMutation::class,
                AdminUpdateDailyTaskMutation::class,
                AdminDeleteDailyTaskMutation::class,
                AdminCreateChallengeMutation::class,
                AdminUpdateChallengeMutation::class,
                AdminDeleteChallengeMutation::class,
                AdminCreateAchievementMutation::class,
                AdminUpdateAchievementMutation::class,
                AdminDeleteAchievementMutation::class,
                AdminCreatePetItemMutation::class,
                AdminUpdatePetItemMutation::class,
                AdminDeletePetItemMutation::class,
                AdminCreateReminderMutation::class,
                AdminUpdateReminderMutation::class,
                AdminDeleteReminderMutation::class,
                AdminCreateDailyRewardMutation::class,
                AdminUpdateDailyRewardMutation::class,
                // Category management
                AdminCreateDailyTaskCategoryMutation::class,
                AdminUpdateDailyTaskCategoryMutation::class,
                AdminDeleteDailyTaskCategoryMutation::class,
                AdminCreateChallengeCategoryMutation::class,
                AdminUpdateChallengeCategoryMutation::class,
                AdminDeleteChallengeCategoryMutation::class,
                AdminCreatePetItemCategoryMutation::class,
                AdminUpdatePetItemCategoryMutation::class,
                AdminDeletePetItemCategoryMutation::class,
                // Media (admin can upload too)
                RequestMediaUploadMutation::class,
                ConfirmMediaUploadMutation::class,
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
        UserErrorType::class,
        ValidationFieldType::class,
        ValidationErrorType::class,
        RateLimitErrorType::class,
        InvalidActionErrorType::class,
        MutationErrorUnionType::class,
        MutationStatusType::class,

        // ── User & profile ───────────────────────────────────────────────────
        UserType::class,
        ProfileType::class,

        // ── Auth ─────────────────────────────────────────────────────────────
        SessionType::class,
        AccessTokenType::class,
        RefreshTokenType::class,
        AuthTokensType::class,

        // ── Media ────────────────────────────────────────────────────────────
        MediaType::class,

        // ── Game ─────────────────────────────────────────────────────────────
        RewardGrantType::class,
        WalletType::class,
        ExpType::class,

        // ── Family ───────────────────────────────────────────────────────────
        FamilyLinkType::class,
        ChildLinkTokenType::class,

        // ── Daily tasks ──────────────────────────────────────────────────────
        DailyTaskCategoryType::class,
        DailyTaskType::class,
        DailyTaskAnalyticsPointType::class,
        ChildDailyTaskType::class,

        // ── Challenges ───────────────────────────────────────────────────────
        ChallengeCategoryType::class,
        ChallengeType::class,
        ChallengeAnalyticsPointType::class,
        ChildChallengeType::class,

        // ── Achievements ─────────────────────────────────────────────────────
        AchievementRequirementsType::class,
        AchievementRequirementsInput::class,
        AchievementType::class,
        ChildAchievementType::class,

        // ── Daily rewards ────────────────────────────────────────────────────
        DailyRewardType::class,

        // ── Reminders ────────────────────────────────────────────────────────
        ReminderType::class,
        ChildReminderType::class,

        // ── Pet shop ─────────────────────────────────────────────────────────
        PetItemCategoryType::class,
        PetItemType::class,
        ChildPetItemType::class,

        // ── Subscriptions & payments ─────────────────────────────────────────
        SubscriptionType::class,
        UserSubscriptionType::class,
        PaymentType::class,

        // ── Child cabinet / progress ────────────────────────────────────────
        ChildCabinetType::class,
        ChildProgressSummaryType::class,

        // ── Payload types (app/GraphQL/Types/Payloads/) ──────────────────────
        MutationPayloadType::class,
        AuthPayloadType::class,
        UpsertProfilePayloadType::class,
        RequestMediaUploadPayloadType::class,
        ConfirmMediaUploadPayloadType::class,
        CreateChildLinkTokenPayloadType::class,
        LinkChildByTokenPayloadType::class,
        FamilyLinkPayloadType::class,
        DailyTaskPayloadType::class,
        ChildDailyTaskPayloadType::class,
        ClaimDailyTaskRewardPayloadType::class,
        ChallengePayloadType::class,
        ChildChallengePayloadType::class,
        ClaimChallengeRewardPayloadType::class,
        AchievementPayloadType::class,
        ClaimAchievementRewardPayloadType::class,
        DailyRewardPayloadType::class,
        ClaimDailyRewardPayloadType::class,
        ReminderPayloadType::class,
        PetItemPayloadType::class,
        PurchasePetItemPayloadType::class,
        ChildPetItemPayloadType::class,
        SubscriptionPayloadType::class,
        PaymentPayloadType::class,
        UserPayloadType::class,
        ExpPayloadType::class,
        WalletPayloadType::class,
        DailyTaskCategoryPayloadType::class,
        ChallengeCategoryPayloadType::class,
        PetItemCategoryPayloadType::class,
    ],

    'error_formatter' => [ErrorFormatter::class, 'format'],

    'errors_handler' => [GraphQL::class, 'handleErrors'],

    /*
     * UserType exposes `children` and `parents` as lists of User, so the schema
     * is self-referential: without a depth cap a single request can nest
     * me { children { parents { children { … } } } } arbitrarily deep and turn
     * into a database amplification attack.
     *
     * Depth 15 clears the deepest legitimate query (~6) with room to spare and
     * still admits the standard introspection query (~12), which would break at
     * a tighter limit while introspection stays enabled.
     */
    'security' => [
        'query_max_complexity' => (int) env('GRAPHQL_MAX_COMPLEXITY', 1000),
        'query_max_depth' => (int) env('GRAPHQL_MAX_DEPTH', 15),
        'disable_introspection' => (bool) env('GRAPHQL_DISABLE_INTROSPECTION', false),
    ],

    'pagination_type' => PaginationType::class,

    'simple_pagination_type' => SimplePaginationType::class,

    'cursor_pagination_type' => CursorPaginationType::class,

    'defaultFieldResolver' => null,

    'headers' => [],

    'json_encoding_options' => 0,

    'apq' => [
        'enable' => env('GRAPHQL_APQ_ENABLE', false),
        'cache_driver' => env('GRAPHQL_APQ_CACHE_DRIVER', config('cache.default')),
        'cache_prefix' => config('cache.prefix').':graphql.apq',
        'cache_ttl' => 300,
    ],

    'execution_middleware' => [
        ValidateOperationParamsMiddleware::class,
        AutomaticPersistedQueriesMiddleware::class,
        AddAuthUserContextValueMiddleware::class,
    ],

    'resolver_middleware_append' => [],
];
