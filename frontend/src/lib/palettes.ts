type PaletteDefinition = Readonly<{
  name: string
  label: string
  description: string
  preview: string
  faviconColor: string
  previewSteps: readonly [string, string, string]
}>

const paletteDefinitions = [
  palette(
    "neutral",
    "Neutre",
    "Sobre et contrasté, proche du thème actuel.",
    "oklch(0.205 0 0)",
    "#d4d4d4",
    ["oklch(0.205 0 0)", "oklch(0.42 0 0)", "oklch(0.62 0 0)"],
  ),
  palette(
    "blue",
    "Bleu atelier",
    "Technique, précis, adapté à une interface de suivi.",
    "oklch(0.55 0.2 255)",
    "#0f7ae5",
    [
      "oklch(0.55 0.2 255)",
      "oklch(0.62 0.16 235)",
      "oklch(0.44 0.14 270)",
    ],
  ),
  palette(
    "slate",
    "Ardoise",
    "Neutre bleuté, très professionnel et discret.",
    "oklch(0.42 0.04 255)",
    "#64748b",
    [
      "oklch(0.42 0.04 255)",
      "oklch(0.52 0.035 245)",
      "oklch(0.34 0.03 265)",
    ],
  ),
  palette(
    "indigo",
    "Indigo diagnostic",
    "Technique, moderne et précis pour les écrans de suivi.",
    "oklch(0.52 0.18 285)",
    "#6366f1",
    [
      "oklch(0.52 0.18 285)",
      "oklch(0.62 0.14 300)",
      "oklch(0.43 0.14 270)",
    ],
  ),
  palette(
    "cyan",
    "Cyan électrique",
    "Frais, digital, très lisible sur les interfaces sombres.",
    "oklch(0.64 0.14 220)",
    "#06b6d4",
    [
      "oklch(0.64 0.14 220)",
      "oklch(0.72 0.12 205)",
      "oklch(0.48 0.11 230)",
    ],
  ),
  palette(
    "teal",
    "Teal contrôle",
    "Entre bleu et vert, propre et industriel.",
    "oklch(0.56 0.13 185)",
    "#0d9488",
    [
      "oklch(0.56 0.13 185)",
      "oklch(0.66 0.11 175)",
      "oklch(0.42 0.09 195)",
    ],
  ),
  palette(
    "emerald",
    "Vert maintenance",
    "Lisible, orienté statut et opérations validées.",
    "oklch(0.58 0.16 155)",
    "#16a34a",
    [
      "oklch(0.58 0.16 155)",
      "oklch(0.64 0.13 170)",
      "oklch(0.38 0.11 145)",
    ],
  ),
  palette(
    "orange",
    "Orange garage",
    "Chaleureux, mécanique, plus marqué visuellement.",
    "oklch(0.64 0.18 55)",
    "#ea580c",
    [
      "oklch(0.64 0.18 55)",
      "oklch(0.7 0.14 75)",
      "oklch(0.48 0.13 40)",
    ],
  ),
  palette(
    "amber",
    "Ambre atelier",
    "Signalétique et outillage, plus doux que l'orange.",
    "oklch(0.69 0.15 80)",
    "#d97706",
    [
      "oklch(0.69 0.15 80)",
      "oklch(0.76 0.12 95)",
      "oklch(0.52 0.12 70)",
    ],
  ),
  palette(
    "rose",
    "Rouge sport",
    "Plus énergique, idéal pour une identité typée performance.",
    "oklch(0.58 0.22 20)",
    "#e11d48",
    [
      "oklch(0.58 0.22 20)",
      "oklch(0.66 0.16 350)",
      "oklch(0.44 0.15 15)",
    ],
  ),
  palette(
    "violet",
    "Violet premium",
    "Plus premium, idéal pour une interface dashboard moderne.",
    "oklch(0.56 0.2 315)",
    "#a855f7",
    [
      "oklch(0.56 0.2 315)",
      "oklch(0.66 0.16 330)",
      "oklch(0.46 0.15 295)",
    ],
  ),
] as const

export const palettes = [...paletteDefinitions]

export type PaletteName = (typeof palettes)[number]["name"]

export const defaultPalette: PaletteName = "neutral"

export function isPaletteName(value: string | null): value is PaletteName {
  return palettes.some((item) => item.name === value)
}

export function applyPalette(paletteName: PaletteName) {
  document.documentElement.dataset.palette = paletteName
  updateFavicon(paletteName)
}

function palette(
  name: string,
  label: string,
  description: string,
  preview: string,
  faviconColor: string,
  previewSteps: readonly [string, string, string],
): PaletteDefinition {
  return { name, label, description, preview, faviconColor, previewSteps }
}

function updateFavicon(paletteName: PaletteName) {
  const selectedPalette = palettes.find((item) => item.name === paletteName)
  const color = selectedPalette?.faviconColor ?? palettes[0].faviconColor
  const href = `data:image/svg+xml,${encodeURIComponent(faviconSvg(color))}`
  let favicon = document.querySelector<HTMLLinkElement>('link[rel="icon"]')

  if (!favicon) {
    favicon = document.createElement("link")
    favicon.rel = "icon"
    favicon.type = "image/svg+xml"
    document.head.appendChild(favicon)
  }

  favicon.href = href
}

function faviconSvg(color: string) {
  const pathD = [
    "M42.5 15.5a12 12 0 0 0-14.2 15.4L15.8 43.4",
    "a5 5 0 0 0 7.1 7.1l12.4-12.4a12 12 0 0 0 15.2-14.4",
    "l-8.1 8.1-6.2-6.2 8.1-8.1Z",
  ].join("")

  return [
    '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"',
    ' viewBox="0 0 64 64">',
    `<path d="${pathD}" fill="none" stroke="${color}"`,
    ' stroke-width="4.5" stroke-linecap="round"',
    ' stroke-linejoin="round"/>',
    '<circle cx="19.4" cy="47" r="2" fill="transparent"/>',
    "</svg>",
  ].join("")
}
