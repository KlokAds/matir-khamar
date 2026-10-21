import DashboardController from './DashboardController'
import MilkController from './MilkController'
import StockController from './StockController'
import OfflineController from './OfflineController'
import CostCenterController from './CostCenterController'
import EntryController from './EntryController'
import AnimalController from './AnimalController'
import ReportController from './ReportController'
import AllocationController from './AllocationController'
import SetupController from './SetupController'
import ImportController from './ImportController'
import SystemController from './SystemController'
import Settings from './Settings'

const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
    MilkController: Object.assign(MilkController, MilkController),
    StockController: Object.assign(StockController, StockController),
    OfflineController: Object.assign(OfflineController, OfflineController),
    CostCenterController: Object.assign(CostCenterController, CostCenterController),
    EntryController: Object.assign(EntryController, EntryController),
    AnimalController: Object.assign(AnimalController, AnimalController),
    ReportController: Object.assign(ReportController, ReportController),
    AllocationController: Object.assign(AllocationController, AllocationController),
    SetupController: Object.assign(SetupController, SetupController),
    ImportController: Object.assign(ImportController, ImportController),
    SystemController: Object.assign(SystemController, SystemController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers