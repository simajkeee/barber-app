<script setup lang="ts">
definePageMeta({
  layout: 'auth',
  middleware: 'guest',
})

const { t } = useI18n()
const route = useRoute()
const localePath = useLocalePath()

const rawToken = route.query.token
const token = typeof rawToken === 'string' ? rawToken : ''

if (!token) {
  await navigateTo(localePath('/forgot-password'), { replace: true })
}

useSeoMeta({
  title: () => t('seo.resetPassword.title'),
  description: () => t('seo.resetPassword.description'),
  ogTitle: () => t('seo.resetPassword.title'),
  ogDescription: () => t('seo.resetPassword.description'),
})
</script>

<template>
  <div>
    <AuthPageHeader :title="t('auth.resetPassword.title')" />

    <div class="rounded-xl bg-white p-6 shadow-card">
      <AuthResetPasswordForm :token="token" />
    </div>

    <AuthFooterLink
      :text="''"
      :link-text="t('auth.resetPassword.backToLogin')"
      to="/login"
    />
  </div>
</template>