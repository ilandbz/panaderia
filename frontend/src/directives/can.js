import { useAuthStore } from '@/stores/auth.store'

export const vCan = {
  mounted(el, binding) {
    const auth = useAuthStore()
    const permission = binding.value
    
    if (!auth.hasPermission(permission)) {
      el.style.display = 'none'
    }
  },
  updated(el, binding) {
    const auth = useAuthStore()
    const permission = binding.value
    
    if (!auth.hasPermission(permission)) {
      el.style.display = 'none'
    } else {
      el.style.display = ''
    }
  }
}
