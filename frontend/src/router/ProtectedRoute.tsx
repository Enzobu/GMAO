import { Navigate, Outlet, useLocation } from "react-router-dom"
import { useAuthStore } from "@/stores/auth-store"

export default function ProtectedRoute() {
  const location = useLocation()
  const isAuthenticated = useAuthStore(
    (state) => state.isAuthenticated,
  )

  if (!isAuthenticated) {
    const redirect = `${location.pathname}${location.search}${location.hash}`
    const loginPath = redirect === "/"
      ? "/login"
      : `/login?redirect=${encodeURIComponent(redirect)}`

    return (
      <Navigate
        to={loginPath}
        replace
      />
    )
  }

  return <Outlet />
}
