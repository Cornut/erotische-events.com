import { defineStore } from 'pinia'

export const useLocaleStore = defineStore('locale', {
  state: () => ({ current: 'de' as 'de' | 'en' }),
  actions: {
    set(locale: 'de' | 'en') {
      this.current = locale
    },
  },
})
