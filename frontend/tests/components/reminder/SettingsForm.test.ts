import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import ReminderSettingsForm from '~/components/reminder/SettingsForm.vue'
import { createReminderSettings } from '~/tests/factories'

describe('ReminderSettingsForm', () => {
  function mountForm(props: { settings?: any; isLoading?: boolean } = {}) {
    return mount(ReminderSettingsForm, {
      props: {
        settings: props.settings ?? createReminderSettings(),
        isLoading: props.isLoading ?? false,
      },
      global: {
        stubs: {
          UiInput: {
            template: '<div><label>{{ label }}</label><input :type="type" :value="modelValue" v-bind="$attrs" @input="$emit(\'update:modelValue\', $event.target.value)" /><span v-if="error" class="error">{{ error }}</span></div>',
            props: ['modelValue', 'label', 'type', 'min', 'max', 'error'],
            emits: ['update:modelValue'],
          },
          UiTextarea: {
            template: '<div><label>{{ label }}</label><textarea :value="modelValue" v-bind="$attrs" @input="$emit(\'update:modelValue\', $event.target.value)" /><span v-if="error" class="error">{{ error }}</span></div>',
            props: ['modelValue', 'label', 'rows', 'maxlength', 'error'],
            emits: ['update:modelValue'],
          },
          UiButton: {
            template: '<button :type="type" :disabled="disabled"><slot /></button>',
            props: ['type', 'disabled'],
          },
        },
      },
    })
  }

  it('renders a form element', () => {
    const wrapper = mountForm()
    expect(wrapper.find('form').exists()).toBe(true)
  })

  it('renders days since last visit input', () => {
    const wrapper = mountForm()
    expect(wrapper.text()).toContain('reminders.settings.daysSinceLastVisit')
  })

  it('renders message template textarea', () => {
    const wrapper = mountForm()
    expect(wrapper.text()).toContain('reminders.settings.messageTemplate')
  })

  it('renders template help text', () => {
    const wrapper = mountForm()
    expect(wrapper.text()).toContain('reminders.settings.templateHelp')
  })

  it('renders save button', () => {
    const wrapper = mountForm()
    expect(wrapper.text()).toContain('reminders.settings.save')
  })

  it('disables save button when isLoading is true', () => {
    const wrapper = mountForm({ isLoading: true })
    const button = wrapper.find('button[type="submit"]')
    expect(button.attributes('disabled')).toBeDefined()
  })

  it('does not disable save button when not loading', () => {
    const wrapper = mountForm({ isLoading: false })
    const button = wrapper.find('button[type="submit"]')
    expect(button.attributes('disabled')).toBeUndefined()
  })

  it('pre-fills inputs from settings prop', () => {
    const settings = createReminderSettings({ daysSinceLastVisit: 14, messageTemplate: 'Test template' })
    const wrapper = mountForm({ settings })
    const inputs = wrapper.findAll('input')
    expect(inputs[0].element.value).toBe('14')
  })
})
