<script setup lang="ts">
definePageMeta({
  layout: 'auth',
  middleware: 'guest',
})

const { t } = useI18n()

useSeoMeta({
  title: () => t('seo.forgotPassword.title'),
  description: () => t('seo.forgotPassword.description'),
  ogTitle: () => t('seo.forgotPassword.title'),
  ogDescription: () => t('seo.forgotPassword.description'),
})
</script>

<template>
  <div>
    <AuthPageHeader :title="t('auth.forgotPassword.title')" />

    <div class="rounded-xl bg-white p-6 shadow-card">
      <AuthForgotPasswordForm />
    </div>

    <AuthFooterLink
      :text="''"
      :link-text="t('auth.forgotPassword.backToLogin')"
      to="/login"
    />
  </div>
</template>