export const UiInputStub = {
  template: '<div class="ui-input"><input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" @blur="$emit(\'blur\')" /><span v-if="error" class="error">{{ error }}</span></div>',
  props: ['modelValue', 'label', 'error', 'type', 'autocomplete', 'required'],
  emits: ['update:modelValue', 'blur'],
}

export const UiButtonStub = {
  template: '<button :type="type || \'button\'" :disabled="loading || disabled"><slot /></button>',
  props: ['loading', 'fullWidth', 'type', 'variant', 'disabled'],
}

export const UiAlertStub = {
  template: '<div class="alert" role="alert">{{ message }}</div>',
  props: ['message', 'type'],
}

export const UiAppLogoStub = {
  template: '<span class="app-logo">BarberPro</span>',
  props: ['size'],
}

export const UiAuthDividerStub = {
  template: '<div class="divider">or</div>',
}

export const authFormStubs = {
  UiInput: UiInputStub,
  UiButton: UiButtonStub,
  UiAlert: UiAlertStub,
}