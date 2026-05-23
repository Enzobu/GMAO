import { createBrowserRouter } from "react-router-dom"

import AppLayout from "@/layouts/AppLayout"
import AuthLayout from "@/layouts/AuthLayout"

import LoginPage from "@/pages/login/LoginPage"
import ResetPasswordPage from "@/pages/reset-password/ResetPasswordPage"
import DashboardPage from "@/pages/dashboard/DashboardPage"
import SettingsPage from "@/pages/settings/SettingsPage"
import ProfilePage from "@/pages/profile/ProfilePage"
import VehiclesPage from "@/pages/vehicles/VehiclesPage"
import VehicleDetailPage from "@/pages/vehicles/VehicleDetailPage"
import VehicleFormPage from "@/pages/vehicles/VehicleFormPage"

import ProtectedRoute from "@/router/ProtectedRoute"

export const router = createBrowserRouter([
  {
    element: <AuthLayout />,
    children: [
      {
        path: "/login",
        element: <LoginPage />,
      },
      {
        path: "/reset-password/reset/:token",
        element: <ResetPasswordPage />,
      },
    ],
  },

  {
    element: <ProtectedRoute />,
    children: [
      {
        element: <AppLayout />,
        children: [
          {
            path: "/",
            element: <DashboardPage />,
          },
          {
            path: "/dashboard",
            element: <DashboardPage />,
          },
          {
            path: "/settings",
            element: <SettingsPage />,
          },
          {
            path: "/profile",
            element: <ProfilePage />,
          },
          {
            path: "/vehicles",
            element: <VehiclesPage />,
          },
          {
            path: "/vehicles/new",
            element: <VehicleFormPage />,
          },
          {
            path: "/vehicles/:id",
            element: <VehicleDetailPage />,
          },
          {
            path: "/vehicles/:id/edit",
            element: <VehicleFormPage />,
          },
        ],
      },
    ],
  },
])
