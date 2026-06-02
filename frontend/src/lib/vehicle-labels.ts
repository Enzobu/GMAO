import { Badge } from "@/components/ui/badge"
import type { ComponentProps } from "react"

type BadgeVariant = ComponentProps<typeof Badge>["variant"]

export const VEHICLE_TYPES = [
  { value: "car", label: "Voiture", variant: "default" },
  { value: "motorcycle", label: "Moto", variant: "secondary" },
  { value: "utility", label: "Utilitaire", variant: "outline" },
  { value: "truck", label: "Camion", variant: "outline" },
  { value: "van", label: "Fourgon", variant: "secondary" },
  { value: "other", label: "Autre", variant: "destructive" },
] as const

export const VEHICLE_STATUSES = [
  { value: "active", label: "Actif", variant: "secondary" },
  { value: "sold", label: "Vendu", variant: "outline" },
  { value: "archived", label: "Archivé", variant: "outline" },
  { value: "inactive", label: "Inactif", variant: "outline" },
  { value: "out_of_service", label: "Hors service", variant: "destructive" },
] as const

export const VEHICLE_FUEL_TYPES = [
  { value: "petrol", label: "Essence", variant: "default" },
  { value: "diesel", label: "Diesel", variant: "destructive" },
  { value: "ethanol", label: "Éthanol (E85)", variant: "secondary" },
  { value: "hybrid", label: "Hybride", variant: "outline" },
  { value: "electric", label: "Électrique", variant: "secondary" },
  { value: "lpg", label: "GPL", variant: "outline" },
  { value: "cng", label: "GNV", variant: "outline" },
  { value: "other", label: "Autre", variant: "outline" },
] as const

export const VEHICLE_TRANSMISSIONS = [
  { value: "manual", label: "Manuelle", variant: "secondary" },
  { value: "automatic", label: "Automatique", variant: "default" },
  { value: "cvt", label: "CVT", variant: "outline" },
  { value: "semi_automatic", label: "Semi-automatique", variant: "outline" },
  { value: "dual_clutch", label: "Double embrayage", variant: "default" },
  { value: "other", label: "Autre", variant: "outline" },
] as const

export const VEHICLE_COLORS = [
  { value: "red", label: "Rouge", variant: "destructive" },
  { value: "green", label: "Vert", variant: "secondary" },
  { value: "blue", label: "Bleu", variant: "default" },
  { value: "pink", label: "Rose", variant: "outline" },
  { value: "purple", label: "Pourpre", variant: "outline" },
  { value: "violet", label: "Violet", variant: "outline" },
  { value: "orange", label: "Orange", variant: "outline" },
  { value: "yellow", label: "Jaune", variant: "outline" },
  { value: "cyan", label: "Cyan", variant: "outline" },
  { value: "gray", label: "Gris", variant: "outline" },
  { value: "black", label: "Noir", variant: "outline" },
  { value: "white", label: "Blanc", variant: "outline" },
] as const

export function vehicleOption(
  collection: readonly { value: string; label: string; variant: string }[],
  value?: string | null,
) {
  return collection.find((item) => item.value === value)
}

export function vehicleBadgeVariant(variant?: string): BadgeVariant {
  if (
    variant === "destructive" ||
    variant === "outline" ||
    variant === "secondary"
  ) {
    return variant
  }

  return "default"
}
