export type DashboardItemType = "maintenance" | "insurance" | "inspection"
export type DashboardItemSeverity = "warning" | "danger"

export interface DashboardStats {
  vehicles: number
  maintenances: number
  maintenanceHealth: {
    percentage: number
    upToDateVehicles: number
    totalVehicles: number
  }
  alerts: number
}

export interface DashboardUpcomingItem {
  type: DashboardItemType
  severity: DashboardItemSeverity
  title: string
  subtitle: string
  date: string
  meta: string
}

export interface DashboardRecentActivityItem {
  type: DashboardItemType
  title: string
  subtitle: string
  date: string
  meta: string
}

export interface DashboardMaintenanceHistoryItem {
  month: string
  count: number
}

export interface DashboardData {
  stats: DashboardStats
  maintenanceHistory: DashboardMaintenanceHistoryItem[]
  upcoming: DashboardUpcomingItem[]
  recentActivity: DashboardRecentActivityItem[]
}
