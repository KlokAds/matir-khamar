import LocaleController from './LocaleController'
import ReminderController from './ReminderController'
import DashboardController from './DashboardController'
import SearchController from './SearchController'
import MilkController from './MilkController'
import StockController from './StockController'
import OfflineController from './OfflineController'
import HerdController from './HerdController'
import HealthController from './HealthController'
import DiaryController from './DiaryController'
import CostCenterController from './CostCenterController'
import EntryController from './EntryController'
import AnimalController from './AnimalController'
import ReportController from './ReportController'
import MoneyController from './MoneyController'
import AllocationController from './AllocationController'
import SetupController from './SetupController'
import ImportController from './ImportController'
import RoleController from './RoleController'
import SystemController from './SystemController'
import Settings from './Settings'

const Controllers = {
    LocaleController: Object.assign(LocaleController, LocaleController),
    ReminderController: Object.assign(ReminderController, ReminderController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    SearchController: Object.assign(SearchController, SearchController),
    MilkController: Object.assign(MilkController, MilkController),
    StockController: Object.assign(StockController, StockController),
    OfflineController: Object.assign(OfflineController, OfflineController),
    HerdController: Object.assign(HerdController, HerdController),
    HealthController: Object.assign(HealthController, HealthController),
    DiaryController: Object.assign(DiaryController, DiaryController),
    CostCenterController: Object.assign(CostCenterController, CostCenterController),
    EntryController: Object.assign(EntryController, EntryController),
    AnimalController: Object.assign(AnimalController, AnimalController),
    ReportController: Object.assign(ReportController, ReportController),
    MoneyController: Object.assign(MoneyController, MoneyController),
    AllocationController: Object.assign(AllocationController, AllocationController),
    SetupController: Object.assign(SetupController, SetupController),
    ImportController: Object.assign(ImportController, ImportController),
    RoleController: Object.assign(RoleController, RoleController),
    SystemController: Object.assign(SystemController, SystemController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers