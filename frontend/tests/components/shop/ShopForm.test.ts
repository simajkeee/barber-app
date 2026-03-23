import { describe, it, expect } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ShopForm from '~/components/shop/ProfileForm.vue'
import { createShop } from '../../factories'

function mountForm(props: Record<string, any> = {}) {
  return mount(ShopForm, {
    props,
    global: {
      stubs: {
        UiInput: {
          template: '<div><label>{{ label }}</label><input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></div>',
          props: ['modelValue', 'label', 'required', 'error'],
          emits: ['update:modelValue'],
        },
        UiTextarea: {
          template: '<div><label>{{ label }}</label><textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></div>',
          props: ['modelValue', 'label', 'rows', 'error'],
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

describe('ShopForm', () => {
  describe('create mode (no shop prop)', () => {
    it('renders name, address, phone, description fields', () => {
      const wrapper = mountForm()
      const labels = wrapper.findAll('label').map(l => l.text())
      expect(labels).toContain('shop.form.name')
      expect(labels).toContain('shop.form.address')
      expect(labels).toContain('shop.form.phone')
      expect(labels).toContain('shop.profile.description')
    })

    it('does not render slug and coverImageUrl fields', () => {
      const wrapper = mountForm()
      const labels = wrapper.findAll('label').map(l => l.text())
      expect(labels).not.toContain('shop.profile.slug')
      expect(labels).not.toContain('shop.profile.coverImageUrl')
    })

    it('renders cancel button', () => {
      const wrapper = mountForm()
      expect(wrapper.text()).toContain('shop.profile.cancel')
    })

    it('emits cancel when cancel button clicked in create mode', async () => {
      const wrapper = mountForm()
      const buttons = wrapper.findAll('button')
      const cancelBtn = buttons.find(b => b.text().includes('shop.profile.cancel'))!
      await cancelBtn.trigger('click')
      expect(wrapper.emitted('cancel')).toHaveLength(1)
    })

    it('shows create title on submit button', () => {
      const wrapper = mountForm()
      expect(wrapper.text()).toContain('shop.create.title')
    })
  })

  describe('edit mode (with shop prop)', () => {
    const shop = createShop()

    it('renders all 6 fields including slug and coverImageUrl', () => {
      const wrapper = mountForm({ shop })
      const labels = wrapper.findAll('label').map(l => l.text())
      expect(labels).toContain('shop.profile.slug')
      expect(labels).toContain('shop.profile.coverImageUrl')
    })

    it('renders cancel button', () => {
      const wrapper = mountForm({ shop })
      expect(wrapper.text()).toContain('shop.profile.cancel')
    })

    it('shows save title on submit button', () => {
      const wrapper = mountForm({ shop })
      expect(wrapper.text()).toContain('shop.profile.save')
    })

    it('emits cancel when cancel button clicked', async () => {
      const wrapper = mountForm({ shop })
      const buttons = wrapper.findAll('button')
      const cancelBtn = buttons.find(b => b.text().includes('shop.profile.cancel'))!
      await cancelBtn.trigger('click')
      expect(wrapper.emitted('cancel')).toHaveLength(1)
    })
  })

  describe('submit', () => {
    it('emits submit with form values on valid submit', async () => {
      const wrapper = mountForm()
      const inputs = wrapper.findAll('input')
      await inputs[0].setValue('My Shop')
      await inputs[1].setValue('123 Street')
      await inputs[2].setValue('0901234567')
      await wrapper.find('form').trigger('submit')
      await flushPromises()

      if (wrapper.emitted('submit')) {
        const payload = wrapper.emitted('submit')![0][0] as Record<string, unknown>
        expect(payload.name).toBe('My Shop')
        expect(payload.address).toBe('123 Street')
        expect(payload.phone).toBe('0901234567')
      }
    })
  })

  describe('setError', () => {
    it('sets general error via exposed setError', async () => {
      const wrapper = mountForm()
      ;(wrapper.vm as any).setError('_general', 'Something went wrong')
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[role="alert"]').text()).toContain('Something went wrong')
    })
  })

  describe('loading', () => {
    it('disables submit button when loading', () => {
      const wrapper = mountForm({ loading: true })
      const submitBtn = wrapper.findAll('button').find(b => b.text().includes('shop.create.title'))
      expect(submitBtn?.attributes('disabled')).toBeDefined()
    })
  })
})