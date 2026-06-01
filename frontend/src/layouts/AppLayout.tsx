import { useState } from "react"
import {
  Bell,
  CarFront,
  House,
  Menu,
  Settings,
  User,
  Warehouse,
  Wrench,
  LogOut,
} from "lucide-react"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { NavLink, Outlet } from "react-router-dom"
import { Button } from "@/components/ui/button"
import { GlobalSearch } from "@/components/global-search"
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet"
import { ThemeToggle } from "@/components/theme-toggle"
import { useAuthStore } from "@/stores/auth-store"

const navigation = [
  {
    label: "Accueil",
    icon: House,
    path: "/dashboard",
  },
  {
    label: "Véhicules",
    icon: CarFront,
    path: "/vehicles",
  },
  {
    label: "Interventions",
    icon: Wrench,
    path: "/interventions",
  },
  {
    label: "Stock",
    icon: Warehouse,
    path: "/parts",
  },
  {
    label: "Utilisateurs",
    icon: User,
    path: "/users",
  },
]

export default function AppLayout() {
  const user = useAuthStore((state) => state.user)
  const logout = useAuthStore((state) => state.logout)
  const isAdmin = user?.roles.includes("ROLE_ADMIN") ?? false
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)

  return (
    <div className="min-h-screen bg-background text-foreground">
      <div className="flex min-w-0">
        {/* Sidebar */}
        <aside
          className={[
            "fixed inset-y-0 left-0 z-50 hidden w-72 border-r",
            "border-border bg-card lg:block",
          ].join(" ")}
        >
          <SidebarContent isAdmin={isAdmin} />
        </aside>

        {/* Main */}
        <div className="flex min-h-screen min-w-0 flex-1 flex-col lg:pl-72">
          {/* Header */}
          <header
            className={[
              "sticky top-0 z-40 border-b border-border",
              "bg-background/80 backdrop-blur",
            ].join(" ")}
          >
            <div
              className={[
                "flex h-16 min-w-0 items-center gap-2 px-3",
                "sm:h-20 sm:gap-4 sm:px-6 lg:px-8",
              ].join(" ")}
            >
              <Sheet
                open={isMobileMenuOpen}
                onOpenChange={setIsMobileMenuOpen}
              >
                <SheetTrigger asChild>
                  <Button
                    variant="outline"
                    size="icon"
                    className="h-10 w-10 shrink-0 rounded-xl lg:hidden"
                  >
                    <Menu className="h-4 w-4" />
                    <span className="sr-only">Ouvrir le menu</span>
                  </Button>
                </SheetTrigger>

                <SheetContent
                  side="left"
                  className="w-[18rem] p-0"
                  showCloseButton={false}
                >
                  <SheetHeader className="sr-only">
                    <SheetTitle>Navigation</SheetTitle>
                  </SheetHeader>

                  <SidebarContent
                    isAdmin={isAdmin}
                    onNavigate={() => setIsMobileMenuOpen(false)}
                  />
                </SheetContent>
              </Sheet>

              <GlobalSearch className="max-w-none flex-1" />

              {/* Right */}
              <div
                className={[
                  "ml-auto flex shrink-0 items-center gap-2",
                  "sm:gap-4",
                ].join(" ")}
              >
                <ThemeToggle />

                <Button
                  variant="outline"
                  size="icon"
                  className="hidden h-11 w-11 rounded-xl sm:inline-flex"
                >
                  <Bell className="h-4 w-4" />
                </Button>

                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <button
                      className={[
                        "flex items-center gap-3 rounded-xl border",
                        "border-border bg-card p-1.5 shadow-sm transition",
                        "hover:bg-muted/40 sm:px-4 sm:py-2",
                      ].join(" ")}
                    >
                      <div
                        className={[
                          "flex h-10 w-10 items-center justify-center",
                          "rounded-xl bg-primary font-semibold",
                          "text-primary-foreground",
                        ].join(" ")}
                      >
                        {user?.initials ?? "?"}
                      </div>

                      <div className="hidden text-left sm:block">
                        <p className="text-sm font-medium">
                          {user?.displayName ?? user?.email ?? "Utilisateur"}
                        </p>

                        <p className="text-xs text-muted-foreground">
                          {isAdmin
                            ? "Administrateur"
                            : "Utilisateur"}
                        </p>
                      </div>
                    </button>
                  </DropdownMenuTrigger>

                  <DropdownMenuContent
                    align="end"
                    className="w-56 rounded-m p-2"
                  >
                    <DropdownMenuItem className="cursor-pointer">
                      <NavLink
                        to="/profile"
                        className="flex w-full items-center"
                      >
                        <Settings className="mr-2 h-4 w-4" />
                        Paramètres du profil
                      </NavLink>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                      variant="destructive"
                      className={[
                        "cursor-pointer text-red-500 focus:text-red-500",
                        "[&_svg]:text-red-500",
                      ].join(" ")}
                      onClick={() => {
                        logout()
                        globalThis.location.href = "/login"
                      }}
                    >
                      <LogOut className="mr-2 h-4 w-4 text-red-500" />
                      Déconnexion
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </div>
          </header>

          {/* Content */}
          <main className="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
            <Outlet />
          </main>
        </div>
      </div>
    </div>
  )
}

type SidebarContentProps = Readonly<{
  isAdmin: boolean
  onNavigate?: () => void
}>

function SidebarContent({ isAdmin, onNavigate }: SidebarContentProps) {
  return (
    <div className="flex h-full flex-col">
      <div className="border-b border-border p-6">
        <div className="flex items-center gap-4">
          <div
            className={[
              "flex h-14 w-14 items-center justify-center rounded-2xl",
              "bg-primary text-primary-foreground shadow-lg",
            ].join(" ")}
          >
            <Wrench className="h-6 w-6" />
          </div>

          <div>
            <h1 className="text-lg font-semibold">GMAO</h1>
            <p className="text-sm text-muted-foreground">
              Gestion d'entretien
            </p>
          </div>
        </div>
      </div>

      <nav className="flex-1 space-y-2 p-4">
        {navigation.map((item) => (
          <SidebarNavLink
            key={item.path}
            item={item}
            onNavigate={onNavigate}
          />
        ))}
      </nav>

      <div className="border-t border-border p-4">
        <SidebarFooterLink to="/settings" onNavigate={onNavigate}>
          Paramètres
        </SidebarFooterLink>

        {isAdmin && (
          <SidebarFooterLink
            to="/configuration"
            onNavigate={onNavigate}
            className="mt-2"
          >
            Configuration
          </SidebarFooterLink>
        )}
      </div>
    </div>
  )
}

type SidebarNavLinkProps = Readonly<{
  item: (typeof navigation)[number]
  onNavigate?: () => void
}>

function SidebarNavLink({ item, onNavigate }: SidebarNavLinkProps) {
  const Icon = item.icon

  return (
    <NavLink
      to={item.path}
      onClick={onNavigate}
      className={({ isActive }) => [
        "flex h-12 items-center gap-3 rounded-xl px-4 text-sm",
        "font-medium transition-all",
        isActive
          ? "bg-primary text-primary-foreground shadow-md"
          : "text-muted-foreground hover:bg-muted hover:text-foreground",
      ].join(" ")}
    >
      <Icon className="h-4 w-4" />
      {item.label}
    </NavLink>
  )
}

type SidebarFooterLinkProps = Readonly<{
  to: string
  children: string
  onNavigate?: () => void
  className?: string
}>

function SidebarFooterLink({
  to,
  children,
  onNavigate,
  className,
}: SidebarFooterLinkProps) {
  return (
    <Button
      variant="ghost"
      className={[
        "h-12 w-full justify-start rounded-xl",
        className,
      ]
        .filter(Boolean)
        .join(" ")}
      asChild
    >
      <NavLink to={to} onClick={onNavigate}>
        <Settings className="mr-3 h-4 w-4" />
        {children}
      </NavLink>
    </Button>
  )
}
