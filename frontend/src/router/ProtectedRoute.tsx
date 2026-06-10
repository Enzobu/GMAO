import { Navigate, Outlet, useLocation } from "react-router-dom"
import { useAuthStore } from "@/stores/auth-store"

export default function ProtectedRoute() {
  const location = useLocation()
  const isAuthenticated = useAuthStore(
    (state) => state.isAuthenticated,
  )

  if (!isAuthenticated) {
    const redirect = `${location.pathname}${location.search}${location.hash}`

    return (
      <Navigate
        to={`/login?redirect=${encodeURIComponent(redirect)}`}
        replace
      />
    )
  }

  return <Outlet />
}
