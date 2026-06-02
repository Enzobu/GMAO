import type { AppUser } from "@/types/user"
import { capitalizeFirstLetter } from "@/lib/text-format"

export function userDisplayName(user: AppUser) {
  return (
    [
      capitalizeFirstLetter(user.firstname),
      capitalizeFirstLetter(user.lastname),
    ].join(" ").trim() ||
    user.email
  )
}

export function userInitials(user: AppUser) {
  return [user.firstname?.[0] ?? "", user.lastname?.[0] ?? ""]
    .join("")
    .toUpperCase() || "?"
}

export function isUserAdmin(user: AppUser) {
  return user.roles.includes("ROLE_ADMIN")
}

export function roleLabel(role: string) {
  if (role === "ROLE_ADMIN") return "Administrateur"
  if (role === "ROLE_USER") return "Utilisateur"
  return role
}
