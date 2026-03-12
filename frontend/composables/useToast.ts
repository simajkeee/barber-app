import { toast } from 'vue-sonner'

export function useToast() {
  const { t } = useI18n()

  return {
    success(key: string) {
      toast.success(t(key))
    },
    error(key: string) {
      toast.error(t(key))
    },
  }
}