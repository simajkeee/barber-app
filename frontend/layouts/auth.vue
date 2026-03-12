<template>
  <div class="flex min-h-screen flex-col bg-gray-50 text-gray-900 antialiased">
    <div class="flex justify-end p-4">
      <UiLanguageSwitcher />
    </div>
    <main class="flex flex-1 items-center justify-center px-4 pb-12">
      <div class="w-full max-w-md">
        <slot />
      </div>
    </main>
  </div>
</template>