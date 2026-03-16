<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
        <span class="text-lg font-semibold text-primary-800">{{ $t('app.name') }}</span>
        <LanguageSwitcher />
      </div>
    </header>
    <main class="max-w-2xl mx-auto px-4 py-6">
      <slot />
    </main>
  </div>
</template>
