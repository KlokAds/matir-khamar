import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import centers from './centers'
import land from './land'
import items from './items'
import accountsDb024e from './accounts'
import partiesBccebf from './parties'
import budget from './budget'
import money from './money'
import importMethod7367d2 from './import'
import rolesF85c84 from './roles'
import update603324 from './update'
import investorsF3f70d from './investors'
import users48860f from './users'
import periods7f6f60 from './periods'
/**
* @see \App\Http\Controllers\SetupController::accounts
* @see app/Http/Controllers/SetupController.php:96
* @route '/setup/accounts'
*/
export const accounts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accounts.url(options),
    method: 'get',
})

accounts.definition = {
    methods: ["get","head"],
    url: '/setup/accounts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SetupController::accounts
* @see app/Http/Controllers/SetupController.php:96
* @route '/setup/accounts'
*/
accounts.url = (options?: RouteQueryOptions) => {
    return accounts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::accounts
* @see app/Http/Controllers/SetupController.php:96
* @route '/setup/accounts'
*/
accounts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accounts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::accounts
* @see app/Http/Controllers/SetupController.php:96
* @route '/setup/accounts'
*/
accounts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: accounts.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SetupController::accounts
* @see app/Http/Controllers/SetupController.php:96
* @route '/setup/accounts'
*/
const accountsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: accounts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::accounts
* @see app/Http/Controllers/SetupController.php:96
* @route '/setup/accounts'
*/
accountsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: accounts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::accounts
* @see app/Http/Controllers/SetupController.php:96
* @route '/setup/accounts'
*/
accountsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: accounts.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

accounts.form = accountsForm

/**
* @see \App\Http\Controllers\SetupController::parties
* @see app/Http/Controllers/SetupController.php:154
* @route '/setup/parties'
*/
export const parties = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: parties.url(options),
    method: 'get',
})

parties.definition = {
    methods: ["get","head"],
    url: '/setup/parties',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SetupController::parties
* @see app/Http/Controllers/SetupController.php:154
* @route '/setup/parties'
*/
parties.url = (options?: RouteQueryOptions) => {
    return parties.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::parties
* @see app/Http/Controllers/SetupController.php:154
* @route '/setup/parties'
*/
parties.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: parties.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::parties
* @see app/Http/Controllers/SetupController.php:154
* @route '/setup/parties'
*/
parties.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: parties.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SetupController::parties
* @see app/Http/Controllers/SetupController.php:154
* @route '/setup/parties'
*/
const partiesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: parties.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::parties
* @see app/Http/Controllers/SetupController.php:154
* @route '/setup/parties'
*/
partiesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: parties.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::parties
* @see app/Http/Controllers/SetupController.php:154
* @route '/setup/parties'
*/
partiesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: parties.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

parties.form = partiesForm

/**
* @see \App\Http\Controllers\ImportController::importMethod
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
export const importMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: importMethod.url(options),
    method: 'get',
})

importMethod.definition = {
    methods: ["get","head"],
    url: '/setup/import',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ImportController::importMethod
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
importMethod.url = (options?: RouteQueryOptions) => {
    return importMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::importMethod
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
importMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: importMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::importMethod
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
importMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: importMethod.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ImportController::importMethod
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
const importMethodForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: importMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::importMethod
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
importMethodForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: importMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ImportController::importMethod
* @see app/Http/Controllers/ImportController.php:22
* @route '/setup/import'
*/
importMethodForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: importMethod.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

importMethod.form = importMethodForm

/**
* @see \App\Http\Controllers\RoleController::roles
* @see app/Http/Controllers/RoleController.php:19
* @route '/setup/roles'
*/
export const roles = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: roles.url(options),
    method: 'get',
})

roles.definition = {
    methods: ["get","head"],
    url: '/setup/roles',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RoleController::roles
* @see app/Http/Controllers/RoleController.php:19
* @route '/setup/roles'
*/
roles.url = (options?: RouteQueryOptions) => {
    return roles.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RoleController::roles
* @see app/Http/Controllers/RoleController.php:19
* @route '/setup/roles'
*/
roles.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: roles.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleController::roles
* @see app/Http/Controllers/RoleController.php:19
* @route '/setup/roles'
*/
roles.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: roles.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RoleController::roles
* @see app/Http/Controllers/RoleController.php:19
* @route '/setup/roles'
*/
const rolesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: roles.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleController::roles
* @see app/Http/Controllers/RoleController.php:19
* @route '/setup/roles'
*/
rolesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: roles.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\RoleController::roles
* @see app/Http/Controllers/RoleController.php:19
* @route '/setup/roles'
*/
rolesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: roles.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

roles.form = rolesForm

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: update.url(options),
    method: 'get',
})

update.definition = {
    methods: ["get","head"],
    url: '/setup/update',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
update.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: update.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
update.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: update.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: update.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
updateForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: update.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
updateForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\InvestorController::investors
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
export const investors = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: investors.url(options),
    method: 'get',
})

investors.definition = {
    methods: ["get","head"],
    url: '/setup/investors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorController::investors
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
investors.url = (options?: RouteQueryOptions) => {
    return investors.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorController::investors
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
investors.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: investors.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::investors
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
investors.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: investors.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorController::investors
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
const investorsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: investors.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::investors
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
investorsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: investors.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::investors
* @see app/Http/Controllers/InvestorController.php:37
* @route '/setup/investors'
*/
investorsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: investors.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

investors.form = investorsForm

/**
* @see \App\Http\Controllers\SetupController::users
* @see app/Http/Controllers/SetupController.php:27
* @route '/setup/users'
*/
export const users = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: users.url(options),
    method: 'get',
})

users.definition = {
    methods: ["get","head"],
    url: '/setup/users',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SetupController::users
* @see app/Http/Controllers/SetupController.php:27
* @route '/setup/users'
*/
users.url = (options?: RouteQueryOptions) => {
    return users.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::users
* @see app/Http/Controllers/SetupController.php:27
* @route '/setup/users'
*/
users.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: users.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::users
* @see app/Http/Controllers/SetupController.php:27
* @route '/setup/users'
*/
users.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: users.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SetupController::users
* @see app/Http/Controllers/SetupController.php:27
* @route '/setup/users'
*/
const usersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: users.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::users
* @see app/Http/Controllers/SetupController.php:27
* @route '/setup/users'
*/
usersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: users.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::users
* @see app/Http/Controllers/SetupController.php:27
* @route '/setup/users'
*/
usersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: users.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

users.form = usersForm

/**
* @see \App\Http\Controllers\SetupController::periods
* @see app/Http/Controllers/SetupController.php:222
* @route '/setup/periods'
*/
export const periods = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: periods.url(options),
    method: 'get',
})

periods.definition = {
    methods: ["get","head"],
    url: '/setup/periods',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SetupController::periods
* @see app/Http/Controllers/SetupController.php:222
* @route '/setup/periods'
*/
periods.url = (options?: RouteQueryOptions) => {
    return periods.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::periods
* @see app/Http/Controllers/SetupController.php:222
* @route '/setup/periods'
*/
periods.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: periods.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::periods
* @see app/Http/Controllers/SetupController.php:222
* @route '/setup/periods'
*/
periods.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: periods.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SetupController::periods
* @see app/Http/Controllers/SetupController.php:222
* @route '/setup/periods'
*/
const periodsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: periods.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::periods
* @see app/Http/Controllers/SetupController.php:222
* @route '/setup/periods'
*/
periodsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: periods.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::periods
* @see app/Http/Controllers/SetupController.php:222
* @route '/setup/periods'
*/
periodsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: periods.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

periods.form = periodsForm

/**
* @see \App\Http\Controllers\SetupController::audit
* @see app/Http/Controllers/SetupController.php:263
* @route '/setup/audit'
*/
export const audit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: audit.url(options),
    method: 'get',
})

audit.definition = {
    methods: ["get","head"],
    url: '/setup/audit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SetupController::audit
* @see app/Http/Controllers/SetupController.php:263
* @route '/setup/audit'
*/
audit.url = (options?: RouteQueryOptions) => {
    return audit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::audit
* @see app/Http/Controllers/SetupController.php:263
* @route '/setup/audit'
*/
audit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: audit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::audit
* @see app/Http/Controllers/SetupController.php:263
* @route '/setup/audit'
*/
audit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: audit.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SetupController::audit
* @see app/Http/Controllers/SetupController.php:263
* @route '/setup/audit'
*/
const auditForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: audit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::audit
* @see app/Http/Controllers/SetupController.php:263
* @route '/setup/audit'
*/
auditForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: audit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SetupController::audit
* @see app/Http/Controllers/SetupController.php:263
* @route '/setup/audit'
*/
auditForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: audit.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

audit.form = auditForm

const setup = {
    centers: Object.assign(centers, centers),
    land: Object.assign(land, land),
    items: Object.assign(items, items),
    accounts: Object.assign(accounts, accountsDb024e),
    parties: Object.assign(parties, partiesBccebf),
    budget: Object.assign(budget, budget),
    money: Object.assign(money, money),
    import: Object.assign(importMethod, importMethod7367d2),
    roles: Object.assign(roles, rolesF85c84),
    update: Object.assign(update, update603324),
    investors: Object.assign(investors, investorsF3f70d),
    users: Object.assign(users, users48860f),
    periods: Object.assign(periods, periods7f6f60),
    audit: Object.assign(audit, audit),
}

export default setup