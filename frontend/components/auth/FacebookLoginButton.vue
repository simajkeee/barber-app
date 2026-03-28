<script lang="ts">
let sdkInitialized = false

function loadFacebookSdk(appId: string): Promise<void> {
  return new Promise<void>((resolve, reject) => {
    if (typeof window === 'undefined') {
      reject(new Error('Facebook SDK requires browser context'))
      return
    }

    if (!appId) {
      reject(new Error('Facebook App ID is not configured'))
      return
    }

    if (sdkInitialized && window.FB) {
      resolve()
      return
    }

    if (window.FB) {
      window.FB.init({ appId, cookie: true, xfbml: false, version: 'v18.0' })
      sdkInitialized = true
      resolve()
      return
    }

    window.fbAsyncInit = () => {
      window.FB.init({ appId, cookie: true, xfbml: false, version: 'v18.0' })
      sdkInitialized = true
      resolve()
    }

    if (document.getElementById('facebook-jssdk')) return

    const script = document.createElement('script')
    script.id = 'facebook-jssdk'
    script.src = 'https://connect.facebook.net/en_US/sdk.js'
    script.async = true
    script.onerror = () => reject(new Error('Failed to load Facebook SDK'))
    document.head.appendChild(script)
  })
}
</script>

<script setup lang="ts">
const emit = defineEmits<{
  success: [payload: { isNewUser?: boolean }]
}>()

const { t } = useI18n()
const { facebookLogin } = useAuth()
const config = useRuntimeConfig()

const isLoading = ref(false)
const error = ref<string | null>(null)

function getFacebookToken(): Promise<string> {
  return new Promise((resolve, reject) => {
    window.FB.login(
      (response: { authResponse?: { accessToken: string } }) => {
        if (response.authResponse?.accessToken) {
          resolve(response.authResponse.accessToken)
        } else {
          reject(new Error('Facebook login cancelled'))
        }
      },
      { scope: 'email,public_profile' },
    )
  })
}

async function onClick() {
  error.value = null
  isLoading.value = true

  try {
    await loadFacebookSdk(config.public.facebookAppId)
    const fbAccessToken = await getFacebookToken()
    const result = await facebookLogin(fbAccessToken)

    if (result.success) {
      emit('success', { isNewUser: result.isNewUser })
    } else {
      error.value = t('auth.error.facebookFailed')
    }
  } catch {
    error.value = t('auth.error.facebookFailed')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <UiButton
      variant="secondary"
      full-width
      :loading="isLoading"
      @click="onClick"
    >
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
      </svg>
      {{ t('auth.facebook.button') }}
    </UiButton>
    <UiAlert v-if="error" :message="error" class="mt-3" />
  </div>
</template>