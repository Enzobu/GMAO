export function capitalizeFirstLetter(value?: string | null) {
  if (!value) {
    return ""
  }

  const trimmed = value.trimStart()
  return trimmed.charAt(0).toUpperCase() + trimmed.slice(1)
}

export function displayValue(value?: string | null) {
  return capitalizeFirstLetter(value) || "—"
}
