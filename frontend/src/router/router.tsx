import { createBrowserRouter } from "react-router-dom"

import AppLayout from "@/layouts/AppLayout"
import AuthLayout from "@/layouts/AuthLayout"

import LoginPage from "@/pages/login/LoginPage"
import RequestResetPasswordPage from "@/pages/reset-password/RequestResetPasswordPage"
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
import InsuranceDetail from "@/pages/vehicle-events/VehicleInsuranceDetailPage"
import InsuranceForm from "@/pages/vehicle-events/VehicleInsuranceFormPage"
import VehicleInsurancesPage from "@/pages/vehicle-events/VehicleInsurancesPage"
import InspectDetail from "@/pages/vehicle-events/VehicleInspectionDetailPage"
import InspectionForm from "@/pages/vehicle-events/VehicleInspectionFormPage"
import InspectionsPage from "@/pages/vehicle-events/VehicleInspectionsPage"
import InterventionDetail from "@/pages/interventions/InterventionDetailPage"
import InterventionFormPage from "@/pages/interventions/InterventionFormPage"
import InterventionsPage from "@/pages/interventions/InterventionsPage"

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
        path: "/reset-password/request",
        element: <RequestResetPasswordPage />,
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
            path: "/vehicles/:vehicleId/insurances",
            element: <VehicleInsurancesPage />,
          },
          {
            path: "/vehicles/:vehicleId/insurances/new",
            element: <InsuranceForm />,
          },
          {
            path: "/vehicles/:vehicleId/insurances/:insuranceId",
            element: <InsuranceDetail />,
          },
          {
            path: "/vehicles/:vehicleId/insurances/:insuranceId/edit",
            element: <InsuranceForm />,
          },
          {
            path: "/vehicles/:vehicleId/inspections",
            element: <InspectionsPage />,
          },
          {
            path: "/vehicles/:vehicleId/inspections/new",
            element: <InspectionForm />,
          },
          {
            path: "/vehicles/:vehicleId/inspections/:inspectionId",
            element: <InspectDetail />,
          },
          {
            path: "/vehicles/:vehicleId/inspections/:inspectionId/edit",
            element: <InspectionForm />,
          },
          {
            path: "/vehicles/:vehicleId/interventions",
            element: <InterventionsPage vehicleScoped />,
          },
          {
            path: "/vehicles/:vehicleId/interventions/new",
            element: <InterventionFormPage />,
          },
          {
            path: "/vehicles/:vehicleId/interventions/:interventionId",
            element: <InterventionDetail vehicleScoped />,
          },
          {
            path: "/vehicles/:vehicleId/interventions/:interventionId/edit",
            element: <InterventionFormPage />,
          },
          {
            path: "/interventions",
            element: <InterventionsPage />,
          },
          {
            path: "/interventions/:id",
            element: <InterventionDetail />,
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
