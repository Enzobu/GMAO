import { create } from "zustand"

import {
  applyPalette,
  defaultPalette,
  isPaletteName,
  type PaletteName,
} from "@/lib/palettes"

const storageKey = "palette"

function getInitialPalette(): PaletteName {
  const storedPalette = localStorage.getItem(storageKey)

  return isPaletteName(storedPalette) ? storedPalette : defaultPalette
}

const initialPalette = getInitialPalette()

applyPalette(initialPalette)

interface PaletteState {
  palette: PaletteName
  setPalette: (palette: PaletteName) => void
}

export const usePaletteStore = create<PaletteState>((set) => ({
  palette: initialPalette,

  setPalette: (palette) => {
    localStorage.setItem(storageKey, palette)
    applyPalette(palette)
    set({ palette })
  },
}))
