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
import ConfigurationPage from "@/pages/configuration/ConfigurationPage"
import NotFoundPage from "@/pages/not-found/NotFoundPage"
import PartsPage from "@/pages/parts/PartsPage"
import PartDetailPage from "@/pages/parts/PartDetailPage"
import PartFormPage from "@/pages/parts/PartFormPage"
import UsersPage from "@/pages/users/UsersPage"
import UserDetailPage from "@/pages/users/UserDetailPage"
import UserFormPage from "@/pages/users/UserFormPage"

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
          {
            path: "/configuration",
            element: <ConfigurationPage />,
          },
          {
            path: "/parts",
            element: <PartsPage />,
          },
          {
            path: "/parts/new",
            element: <PartFormPage />,
          },
          {
            path: "/parts/:id",
            element: <PartDetailPage />,
          },
          {
            path: "/parts/:id/edit",
            element: <PartFormPage />,
          },
          {
            path: "/users",
            element: <UsersPage />,
          },
          {
            path: "/users/new",
            element: <UserFormPage />,
          },
          {
            path: "/users/:id",
            element: <UserDetailPage />,
          },
          {
            path: "/users/:id/edit",
            element: <UserFormPage />,
          },
          {
            path: "*",
            element: <NotFoundPage />,
          },
        ],
      },
    ],
  },
])
