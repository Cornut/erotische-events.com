import { defineStore } from 'pinia'

export const useAuthModalStore = defineStore('authModal', {
  state: () => ({
    show: false,
    mode: 'login' as 'login' | 'register',
    // Event the guest tried to favorite; auto-favorited after a successful login.
    pendingFavoriteEventId: null as number | null,
  }),
  actions: {
    open(mode: 'login' | 'register' = 'login') {
      this.mode = mode
      this.show = true
    },
    requestFavorite(eventId: number) {
      this.pendingFavoriteEventId = eventId
      this.mode = 'login'
      this.show = true
    },
    consumePendingFavorite(): number | null {
      const id = this.pendingFavoriteEventId
      this.pendingFavoriteEventId = null
      return id
    },
    close() {
      this.show = false
    },
  },
})
