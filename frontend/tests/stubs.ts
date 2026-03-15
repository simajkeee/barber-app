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

// Shared stubs for AppointmentDailyCard and AppointmentCard (list view)
// Both components compose the same five child components identically.
export const appointmentCardChildStubs = {
  AppointmentTimeBadge: { template: '<span>time</span>', props: ['startTime', 'endTime', 'showDate'] },
  AppointmentClientInfo: { template: '<span>{{ client.firstName }}</span>', props: ['client'] },
  AppointmentServiceInfo: { template: '<span>{{ service.name }}</span>', props: ['service'] },
  AppointmentStatusBadge: { template: '<span>{{ status }}</span>', props: ['status'] },
  AppointmentQuickActions: {
    template: `<div>
      <button class="btn-view" @click="$emit('view')">view</button>
      <button class="btn-complete" @click="$emit('complete')">complete</button>
      <button class="btn-no-show" @click="$emit('noShow')">noShow</button>
      <button class="btn-cancel" @click="$emit('cancel')">cancel</button>
    </div>`,
    props: ['status', 'loading'],
    emits: ['view', 'complete', 'noShow', 'cancel'],
  },
}