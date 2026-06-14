export function softDeleteDescription(label: string) {
  return [
    `${label} sera masqué de la plateforme.`,
    "Aucune donnée ne sera supprimée définitivement.",
  ].join(" ")
}
