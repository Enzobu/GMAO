import { Monitor, Moon, Sun } from "lucide-react"
import { useTheme } from "next-themes"

import { Button } from "@/components/ui/button"

const themes = ["system", "light", "dark"] as const
type ThemeName = (typeof themes)[number]

function isThemeName(theme: string | undefined): theme is ThemeName {
  return themes.includes(theme as ThemeName)
}

const themeIcons = {
  system: Monitor,
  light: Sun,
  dark: Moon,
}

const themeLabels = {
  system: "Thème système",
  light: "Thème clair",
  dark: "Thème sombre",
}

export function ThemeToggle() {
  const { theme, setTheme } = useTheme()
  const currentTheme = isThemeName(theme) ? theme : "system"
  const nextTheme = themes[(themes.indexOf(currentTheme) + 1) % themes.length]
  const Icon = themeIcons[currentTheme]
  const label = themeLabels[currentTheme]

  return (
    <Button
      type="button"
      variant="outline"
      size="icon"
      className="h-11 w-11 rounded-xl"
      title={`${label}. Cliquer pour passer au thème ${nextTheme}.`}
      onClick={() => setTheme(nextTheme)}
    >
      <Icon className="h-4 w-4" />
      <span className="sr-only">Changer de thème</span>
    </Button>
  )
}
