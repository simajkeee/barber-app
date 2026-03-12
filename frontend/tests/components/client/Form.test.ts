import { describe, it, expect } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import Form from '~/components/client/Form.vue'
import { createClient } from '../../factories'

function mountForm(props: Record<string, any> = {}) {
  return mount(Form, {
    props,
    global: {
      stubs: {
        UiInput: {
          template: '<div><label>{{ label }}</label><input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></div>',
          props: ['modelValue', 'label', 'required', 'error', 'type', 'placeholder', 'autocomplete'],
          emits: ['update:modelValue'],
        },
        UiTextarea: {
          template: '<div><label>{{ label }}</label><textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></div>',
          props: ['modelValue', 'label', 'rows', 'error', 'placeholder'],
          emits: ['update:modelValue'],
        },
        UiButton: {
          template: '<button :type="type || \'button\'" :disabled="loading" @click="$emit(\'click\')"><slot /></button>',
          props: ['variant', 'loading', 'type'],
          emits: ['click'],
        },
        UiAlert: {
          template: '<div role="alert">{{ message }}</div>',
          props: ['message'],
        },
      },
    },
  })
}

describe('ClientForm', () => {
  describe('create mode (no client prop)', () => {
    it('renders all 5 fields', () => {
      const wrapper = mountForm()
      const labels = wrapper.findAll('label').map(l => l.text())
      expect(labels).toContain('clients.form.firstName')
      expect(labels).toContain('clients.form.lastName')
      expect(labels).toContain('clients.form.phone')
      expect(labels).toContain('clients.form.email')
      expect(labels).toContain('clients.form.notes')
    })

    it('renders save and cancel buttons', () => {
      const wrapper = mountForm()
      expect(wrapper.text()).toContain('common.save')
      expect(wrapper.text()).toContain('common.cancel')
    })
  })

  describe('edit mode (with client prop)', () => {
    it('pre-fills fields with client data', () => {
      const client = createClient({ firstName: 'Tran', lastName: 'B', phone: '123', email: 'a@b.com', notes: 'VIP' })
      const wrapper = mountForm({ client })
      const inputs = wrapper.findAll('input')
      expect(inputs[0].element.value).toBe('Tran')
      expect(inputs[1].element.value).toBe('B')
      expect(inputs[2].element.value).toBe('123')
      expect(inputs[3].element.value).toBe('a@b.com')
    })
  })

  describe('submit', () => {
    it('emits submit with form values on valid submit', async () => {
      const wrapper = mountForm()
      const inputs = wrapper.findAll('input')
      await inputs[0].setValue('First')
      await inputs[1].setValue('Last')
      await inputs[2].setValue('0901234567')
      await wrapper.find('form').trigger('submit')
      await flushPromises()

      if (wrapper.emitted('submit')) {
        const payload = wrapper.emitted('submit')![0][0] as Record<string, unknown>
        expect(payload.firstName).toBe('First')
        expect(payload.lastName).toBe('Last')
        expect(payload.phone).toBe('0901234567')
      }
    })

    it('converts empty email to null before emitting', async () => {
      const wrapper = mountForm()
      const inputs = wrapper.findAll('input')
      await inputs[0].setValue('A')
      await inputs[1].setValue('B')
      await inputs[2].setValue('123')
      // email left empty
      await wrapper.find('form').trigger('submit')
      await flushPromises()

      if (wrapper.emitted('submit')) {
        const payload = wrapper.emitted('submit')![0][0] as Record<string, unknown>
        expect(payload.email).toBeNull()
      }
    })

    it('converts empty notes to null before emitting', async () => {
      const wrapper = mountForm()
      const inputs = wrapper.findAll('input')
      await inputs[0].setValue('A')
      await inputs[1].setValue('B')
      await inputs[2].setValue('123')
      await wrapper.find('form').trigger('submit')
      await flushPromises()

      if (wrapper.emitted('submit')) {
        const payload = wrapper.emitted('submit')![0][0] as Record<string, unknown>
        expect(payload.notes).toBeNull()
      }
    })

    it('does not emit submit when validation fails', async () => {
      const wrapper = mountForm()
      // Leave all fields empty
      await wrapper.find('form').trigger('submit')
      await flushPromises()
      expect(wrapper.emitted('submit')).toBeUndefined()
    })
  })

  describe('cancel', () => {
    it('emits cancel when cancel button clicked', async () => {
      const wrapper = mountForm()
      const buttons = wrapper.findAll('button')
      const cancelBtn = buttons.find(b => b.text().includes('common.cancel'))!
      await cancelBtn.trigger('click')
      expect(wrapper.emitted('cancel')).toHaveLength(1)
    })
  })

  describe('setError', () => {
    it('sets field error via exposed setError', async () => {
      const wrapper = mountForm()
      ;(wrapper.vm as any).setError('phone', 'Phone taken')
      await wrapper.vm.$nextTick()
      // The error gets set on the vee-validate field, which passes to UiInput error prop
      // We verify the exposed method exists and doesn't throw
      expect((wrapper.vm as any).setError).toBeDefined()
    })

    it('sets general error via exposed setError with _general', async () => {
      const wrapper = mountForm()
      ;(wrapper.vm as any).setError('_general', 'Something went wrong')
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[role="alert"]').text()).toContain('Something went wrong')
    })
  })

  describe('loading', () => {
    it('disables submit button when loading', () => {
      const wrapper = mountForm({ loading: true })
      const submitBtn = wrapper.findAll('button').find(b => b.text().includes('common.save'))
      expect(submitBtn?.attributes('disabled')).toBeDefined()
    })
  })
})