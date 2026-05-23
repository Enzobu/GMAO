export const palettes = [
  {
    name: "neutral",
    label: "Neutre",
    description: "Sobre et contrasté, proche du thème actuel.",
    preview: "oklch(0.205 0 0)",
    faviconColor: "#d4d4d4",
    previewSteps: ["oklch(0.205 0 0)", "oklch(0.42 0 0)", "oklch(0.62 0 0)"],
  },
  {
    name: "blue",
    label: "Bleu atelier",
    description: "Technique, précis, adapté à une interface de suivi.",
    preview: "oklch(0.55 0.2 255)",
    faviconColor: "#0f7ae5",
    previewSteps: ["oklch(0.55 0.2 255)", "oklch(0.62 0.16 235)", "oklch(0.44 0.14 270)"],
  },
  {
    name: "slate",
    label: "Ardoise",
    description: "Neutre bleuté, très professionnel et discret.",
    preview: "oklch(0.42 0.04 255)",
    faviconColor: "#64748b",
    previewSteps: ["oklch(0.42 0.04 255)", "oklch(0.52 0.035 245)", "oklch(0.34 0.03 265)"],
  },
  {
    name: "indigo",
    label: "Indigo diagnostic",
    description: "Technique, moderne et précis pour les écrans de suivi.",
    preview: "oklch(0.52 0.18 285)",
    faviconColor: "#6366f1",
    previewSteps: ["oklch(0.52 0.18 285)", "oklch(0.62 0.14 300)", "oklch(0.43 0.14 270)"],
  },
  {
    name: "cyan",
    label: "Cyan électrique",
    description: "Frais, digital, très lisible sur les interfaces sombres.",
    preview: "oklch(0.64 0.14 220)",
    faviconColor: "#06b6d4",
    previewSteps: ["oklch(0.64 0.14 220)", "oklch(0.72 0.12 205)", "oklch(0.48 0.11 230)"],
  },
  {
    name: "teal",
    label: "Teal contrôle",
    description: "Entre bleu et vert, propre et industriel.",
    preview: "oklch(0.56 0.13 185)",
    faviconColor: "#0d9488",
    previewSteps: ["oklch(0.56 0.13 185)", "oklch(0.66 0.11 175)", "oklch(0.42 0.09 195)"],
  },
  {
    name: "emerald",
    label: "Vert maintenance",
    description: "Lisible, orienté statut et opérations validées.",
    preview: "oklch(0.58 0.16 155)",
    faviconColor: "#16a34a",
    previewSteps: ["oklch(0.58 0.16 155)", "oklch(0.64 0.13 170)", "oklch(0.38 0.11 145)"],
  },
  {
    name: "orange",
    label: "Orange garage",
    description: "Chaleureux, mécanique, plus marqué visuellement.",
    preview: "oklch(0.64 0.18 55)",
    faviconColor: "#ea580c",
    previewSteps: ["oklch(0.64 0.18 55)", "oklch(0.7 0.14 75)", "oklch(0.48 0.13 40)"],
  },
  {
    name: "amber",
    label: "Ambre atelier",
    description: "Signalétique et outillage, plus doux que l'orange.",
    preview: "oklch(0.69 0.15 80)",
    faviconColor: "#d97706",
    previewSteps: ["oklch(0.69 0.15 80)", "oklch(0.76 0.12 95)", "oklch(0.52 0.12 70)"],
  },
  {
    name: "rose",
    label: "Rouge sport",
    description: "Plus énergique, idéal pour une identité typée performance.",
    preview: "oklch(0.58 0.22 20)",
    faviconColor: "#e11d48",
    previewSteps: ["oklch(0.58 0.22 20)", "oklch(0.66 0.16 350)", "oklch(0.44 0.15 15)"],
  },
  {
    name: "violet",
    label: "Violet premium",
    description: "Plus premium, idéal pour une interface dashboard moderne.",
    preview: "oklch(0.56 0.2 315)",
    faviconColor: "#a855f7",
    previewSteps: ["oklch(0.56 0.2 315)", "oklch(0.66 0.16 330)", "oklch(0.46 0.15 295)"],
  },
] as const

export type PaletteName = (typeof palettes)[number]["name"]

export const defaultPalette: PaletteName = "neutral"

export function isPaletteName(value: string | null): value is PaletteName {
  return palettes.some((palette) => palette.name === value)
}

export function applyPalette(palette: PaletteName) {
  document.documentElement.dataset.palette = palette
  updateFavicon(palette)
}

function updateFavicon(paletteName: PaletteName) {
  const palette = palettes.find((item) => item.name === paletteName)
  const color = palette?.faviconColor ?? palettes[0].faviconColor
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">
    <path d="M42.5 15.5a12 12 0 0 0-14.2 15.4L15.8 43.4a5 5 0 0 0 7.1 7.1l12.4-12.4a12 12 0 0 0 15.2-14.4l-8.1 8.1-6.2-6.2 8.1-8.1Z" fill="none" stroke="${color}" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="19.4" cy="47" r="2" fill="transparent"/>
  </svg>`
  const href = `data:image/svg+xml,${encodeURIComponent(svg)}`
  let favicon = document.querySelector<HTMLLinkElement>('link[rel="icon"]')

  if (!favicon) {
    favicon = document.createElement("link")
    favicon.rel = "icon"
    favicon.type = "image/svg+xml"
    document.head.appendChild(favicon)
  }

  favicon.href = href
}
