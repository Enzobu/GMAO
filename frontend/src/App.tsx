import { useEffect } from "react"
import { RouterProvider } from "react-router-dom"

import { getMe } from "@/api/auth"
import { ThemeProvider } from "@/components/theme-provider"
import { router } from "@/router/router"
import { useAuthStore } from "@/stores/auth-store"
import "@/stores/palette-store"

function App() {
  const token = useAuthStore((state) => state.token)
  const user = useAuthStore((state) => state.user)
  const setUser = useAuthStore((state) => state.setUser)
  const logout = useAuthStore((state) => state.logout)

  useEffect(() => {
    const { pathname } = globalThis.location
    const isPublicRoute = pathname === "/login"
      || pathname.startsWith("/reset-password")

    if (!token || user || isPublicRoute) {
      return
    }

    void getMe()
      .then(setUser)
      .catch(logout)
  }, [logout, setUser, token, user])

  return (
    <ThemeProvider>
      <RouterProvider router={router} />
    </ThemeProvider>
  )
}

export default App
