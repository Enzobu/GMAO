import type { AppUser } from "@/types/user"

export function userDisplayName(user: AppUser) {
  return `${capitalize(user.firstname)} ${user.lastname?.toUpperCase() ?? ""}`.trim() || user.email
}

export function userInitials(user: AppUser) {
  return `${user.firstname?.[0] ?? ""}${user.lastname?.[0] ?? ""}`.toUpperCase() || "?"
}

export function isUserAdmin(user: AppUser) {
  return user.roles.includes("ROLE_ADMIN")
}

export function roleLabel(role: string) {
  if (role === "ROLE_ADMIN") return "Administrateur"
  if (role === "ROLE_USER") return "Utilisateur"
  return role
}

function capitalize(value?: string | null) {
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : ""
}
