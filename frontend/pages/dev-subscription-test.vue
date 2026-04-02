<script setup lang="ts">
// Temporary dev-only test page - renders subscription components with mock data
definePageMeta({ layout: false })
</script>

<template>
  <div class="min-h-screen bg-gray-100 p-8">
    <h1 class="mb-8 text-2xl font-bold text-gray-900">Subscription Components Visual Test</h1>

    <!-- Scenario 1: FREE plan, active -->
    <section class="mb-12">
      <h2 class="mb-4 text-lg font-semibold text-gray-700">Scenario 1: FREE plan — active (18/50 used)</h2>
      <div class="grid gap-6 md:grid-cols-2">
        <SubscriptionPlanCard
          plan="free"
          status="active"
          start-date="2026-03-01T00:00:00+07:00"
          :end-date="null"
        />
        <SubscriptionUsageCard
          :appointments-this-month="18"
          :appointment-limit="50"
          :limit-reached="false"
        />
      </div>
      <div class="mt-4">
        <SubscriptionUpgradePrompt plan="free" status="active" :is-expiring-soon="false" />
      </div>
    </section>

    <!-- Scenario 2: FREE plan, limit reached -->
    <section class="mb-12">
      <h2 class="mb-4 text-lg font-semibold text-gray-700">Scenario 2: FREE plan — limit reached (50/50)</h2>
      <div class="grid gap-6 md:grid-cols-2">
        <SubscriptionPlanCard
          plan="free"
          status="active"
          start-date="2026-03-01T00:00:00+07:00"
          :end-date="null"
        />
        <SubscriptionUsageCard
          :appointments-this-month="50"
          :appointment-limit="50"
          :limit-reached="true"
        />
      </div>
      <div class="mt-4">
        <SubscriptionUpgradePrompt plan="free" status="active" :is-expiring-soon="false" />
      </div>
    </section>

    <!-- Scenario 3: PRO plan, active -->
    <section class="mb-12">
      <h2 class="mb-4 text-lg font-semibold text-gray-700">Scenario 3: PRO plan — active (67 used, unlimited)</h2>
      <div class="grid gap-6 md:grid-cols-2">
        <SubscriptionPlanCard
          plan="pro"
          status="active"
          start-date="2026-03-15T00:00:00+07:00"
          end-date="2026-04-14T00:00:00+07:00"
          :days-remaining="25"
        />
        <SubscriptionUsageCard
          :appointments-this-month="67"
          :appointment-limit="null"
          :limit-reached="false"
        />
      </div>
      <div class="mt-4">
        <SubscriptionUpgradePrompt plan="pro" status="active" :is-expiring-soon="false" />
      </div>
    </section>

    <!-- Scenario 4: PRO plan, expired -->
    <section class="mb-12">
      <h2 class="mb-4 text-lg font-semibold text-gray-700">Scenario 4: PRO plan — expired</h2>
      <div class="grid gap-6 md:grid-cols-2">
        <SubscriptionPlanCard
          plan="free"
          status="expired"
          start-date="2026-02-15T00:00:00+07:00"
          end-date="2026-03-15T00:00:00+07:00"
        />
        <SubscriptionUsageCard
          :appointments-this-month="12"
          :appointment-limit="50"
          :limit-reached="false"
        />
      </div>
    </section>

    <!-- Scenario 5: CANCELLED -->
    <section class="mb-12">
      <h2 class="mb-4 text-lg font-semibold text-gray-700">Scenario 5: Subscription cancelled</h2>
      <div class="grid gap-6 md:grid-cols-2">
        <SubscriptionPlanCard
          plan="free"
          status="cancelled"
          start-date="2026-01-01T00:00:00+07:00"
          :end-date="null"
        />
        <SubscriptionUsageCard
          :appointments-this-month="5"
          :appointment-limit="50"
          :limit-reached="false"
        />
      </div>
    </section>

    <!-- Scenario 6: Near limit (80%+ usage) -->
    <section class="mb-12">
      <h2 class="mb-4 text-lg font-semibold text-gray-700">Scenario 6: FREE plan — near limit (42/50 = 84%)</h2>
      <div class="grid gap-6 md:grid-cols-2">
        <SubscriptionPlanCard
          plan="free"
          status="active"
          start-date="2026-03-01T00:00:00+07:00"
          :end-date="null"
        />
        <SubscriptionUsageCard
          :appointments-this-month="42"
          :appointment-limit="50"
          :limit-reached="false"
        />
      </div>
    </section>
  </div>
</template>
