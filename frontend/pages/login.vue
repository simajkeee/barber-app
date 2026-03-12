<script setup lang="ts">
definePageMeta({
  layout: 'auth',
  middleware: 'guest',
})

const { t } = useI18n()
const localePath = useLocalePath()

useSeoMeta({
  title: () => t('seo.login.title'),
  description: () => t('seo.login.description'),
  ogTitle: () => t('seo.login.title'),
  ogDescription: () => t('seo.login.description'),
})

function onSuccess() {
  navigateTo(localePath('/dashboard'))
}
</script>

<template>
  <div>
    <AuthPageHeader :title="t('auth.login.title')" />

    <div class="rounded-xl bg-white p-6 shadow-card">
      <AuthLoginForm @success="onSuccess" />
      <AuthDivider />
      <AuthFacebookLoginButton @success="onSuccess" />
    </div>

    <AuthFooterLink
      :text="t('auth.login.noAccount')"
      :link-text="t('auth.login.registerLink')"
      to="/register"
    />
  </div>
</template>