<script setup lang="ts">
definePageMeta({
  layout: 'auth',
  middleware: 'guest',
})

const { t } = useI18n()
const localePath = useLocalePath()

useSeoMeta({
  title: () => t('seo.register.title'),
  description: () => t('seo.register.description'),
  ogTitle: () => t('seo.register.title'),
  ogDescription: () => t('seo.register.description'),
})

function onSuccess() {
  navigateTo(localePath('/dashboard'))
}
</script>

<template>
  <div>
    <AuthPageHeader :title="t('auth.register.title')" />

    <div class="rounded-xl bg-white p-6 shadow-card">
      <AuthRegisterForm @success="onSuccess" />
      <AuthDivider />
      <AuthFacebookLoginButton @success="onSuccess" />
      <p class="mt-4 text-center text-xs text-gray-400">
        {{ t('auth.register.termsPrefix') }}
        <NuxtLink :to="localePath('/terms')" class="underline hover:text-gray-600">{{ t('auth.register.termsLink') }}</NuxtLink>
        {{ t('auth.register.termsAnd') }}
        <NuxtLink :to="localePath('/privacy')" class="underline hover:text-gray-600">{{ t('auth.register.privacyLink') }}</NuxtLink>
      </p>
    </div>

    <AuthFooterLink
      :text="t('auth.register.hasAccount')"
      :link-text="t('auth.register.loginLink')"
      to="/login"
    />
  </div>
</template>