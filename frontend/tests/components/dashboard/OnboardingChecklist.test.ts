import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref, computed } from 'vue'
import OnboardingChecklist from '~/components/dashboard/OnboardingChecklist.vue'

const mockDismiss = vi.fn()

function createMockOnboardingStore(overrides: Record<string, any> = {}) {
  return {
    shopCreated: true,
    serviceAdded: false,
    scheduleConfigured: true,
    clientAdded: false,
    isOnboarding: true,
    isDismissed: false,
    dismiss: mockDismiss,
    ...overrides,
  }
}

let mockStore: ReturnType<typeof createMockOnboardingStore>

vi.stubGlobal('useOnboardingStore', () => mockStore)

describe('OnboardingChecklist', () => {
  beforeEach(() => {
    mockDismiss.mockReset()
    mockStore = createMockOnboardingStore()
  })

  function mountChecklist() {
    return mount(OnboardingChecklist)
  }

  describe('rendering', () => {
    it('renders all 4 steps', () => {
      const wrapper = mountChecklist()
      const items = wrapper.findAll('li')
      expect(items).toHaveLength(4)
    })

    it('renders title and subtitle', () => {
      const wrapper = mountChecklist()
      expect(wrapper.text()).toContain('onboarding.title')
      expect(wrapper.text()).toContain('onboarding.subtitle')
    })

    it('renders step labels via i18n keys', () => {
      const wrapper = mountChecklist()
      const text = wrapper.text()
      expect(text).toContain('onboarding.steps.shop')
      expect(text).toContain('onboarding.steps.service')
      expect(text).toContain('onboarding.steps.schedule')
      expect(text).toContain('onboarding.steps.client')
    })
  })

  describe('visibility', () => {
    it('renders when isOnboarding is true', () => {
      const wrapper = mountChecklist()
      expect(wrapper.find('div').exists()).toBe(true)
    })

    it('does not render content when isOnboarding is false', () => {
      mockStore = createMockOnboardingStore({ isOnboarding: false })
      const wrapper = mountChecklist()
      expect(wrapper.find('ul').exists()).toBe(false)
    })
  })

  describe('step completion icons', () => {
    it('shows filled checkmark for completed steps', () => {
      mockStore = createMockOnboardingStore({ shopCreated: true, scheduleConfigured: true })
      const wrapper = mountChecklist()
      // Shop step (first) should have filled checkmark (fill="currentColor")
      const firstStep = wrapper.findAll('li')[0]
      const svg = firstStep.find('svg')
      expect(svg.attributes('fill')).toBe('currentColor')
      expect(svg.classes()).toContain('text-green-500')
    })

    it('shows circle outline for incomplete steps', () => {
      mockStore = createMockOnboardingStore({ serviceAdded: false })
      const wrapper = mountChecklist()
      // Service step (second) should have circle outline (fill="none", stroke)
      const secondStep = wrapper.findAll('li')[1]
      const svg = secondStep.find('svg')
      expect(svg.attributes('fill')).toBe('none')
      expect(svg.attributes('stroke')).toBe('currentColor')
      expect(svg.classes()).toContain('text-gray-300')
    })
  })

  describe('step links', () => {
    it('all steps are clickable NuxtLink elements', () => {
      const wrapper = mountChecklist()
      const links = wrapper.findAll('li a')
      expect(links).toHaveLength(4)
    })

    it('completed steps link to correct route', () => {
      mockStore = createMockOnboardingStore({ shopCreated: true })
      const wrapper = mountChecklist()
      const shopLink = wrapper.findAll('li a')[0]
      expect(shopLink.attributes('href')).toBe('/dashboard/shop/create')
    })

    it('incomplete steps link to correct route', () => {
      mockStore = createMockOnboardingStore({ serviceAdded: false })
      const wrapper = mountChecklist()
      const serviceLink = wrapper.findAll('li a')[1]
      expect(serviceLink.attributes('href')).toBe('/dashboard/shop/services')
    })

    it('step 3 links to schedule page', () => {
      const wrapper = mountChecklist()
      const scheduleLink = wrapper.findAll('li a')[2]
      expect(scheduleLink.attributes('href')).toBe('/dashboard/shop/schedule')
    })

    it('step 4 links to clients create page', () => {
      const wrapper = mountChecklist()
      const clientLink = wrapper.findAll('li a')[3]
      expect(clientLink.attributes('href')).toBe('/dashboard/clients/create')
    })
  })

  describe('completed step styling', () => {
    it('applies line-through to completed step label', () => {
      mockStore = createMockOnboardingStore({ shopCreated: true })
      const wrapper = mountChecklist()
      const shopSpan = wrapper.findAll('li')[0].find('span')
      // The span with the label text should have line-through
      const labelSpan = wrapper.findAll('li')[0].findAll('span').find(
        s => s.text() === 'onboarding.steps.shop',
      )
      expect(labelSpan?.classes()).toContain('line-through')
    })

    it('does not apply line-through to incomplete step label', () => {
      mockStore = createMockOnboardingStore({ serviceAdded: false })
      const wrapper = mountChecklist()
      const labelSpan = wrapper.findAll('li')[1].findAll('span').find(
        s => s.text() === 'onboarding.steps.service',
      )
      expect(labelSpan?.classes()).not.toContain('line-through')
    })
  })

  describe('dismiss', () => {
    it('calls onboardingStore.dismiss when dismiss button clicked', async () => {
      const wrapper = mountChecklist()
      const dismissBtn = wrapper.find('button')
      await dismissBtn.trigger('click')
      expect(mockDismiss).toHaveBeenCalledOnce()
    })

    it('renders dismiss button text', () => {
      const wrapper = mountChecklist()
      expect(wrapper.find('button').text()).toContain('onboarding.dismiss')
    })
  })

  describe('accessibility', () => {
    it('has role="list" on the ul', () => {
      const wrapper = mountChecklist()
      expect(wrapper.find('ul').attributes('role')).toBe('list')
    })

    it('has aria-label on each step link', () => {
      const wrapper = mountChecklist()
      const links = wrapper.findAll('li a')
      links.forEach(link => {
        expect(link.attributes('aria-label')).toBeTruthy()
      })
    })

    it('completed step aria-label contains completed text', () => {
      mockStore = createMockOnboardingStore({ shopCreated: true })
      const wrapper = mountChecklist()
      const shopLink = wrapper.findAll('li a')[0]
      expect(shopLink.attributes('aria-label')).toContain('onboarding.completed')
    })

    it('incomplete step aria-label contains not completed text', () => {
      mockStore = createMockOnboardingStore({ serviceAdded: false })
      const wrapper = mountChecklist()
      const serviceLink = wrapper.findAll('li a')[1]
      expect(serviceLink.attributes('aria-label')).toContain('onboarding.notCompleted')
    })

    it('dismiss button has aria-label', () => {
      const wrapper = mountChecklist()
      expect(wrapper.find('button').attributes('aria-label')).toBe('onboarding.dismiss')
    })
  })
})
