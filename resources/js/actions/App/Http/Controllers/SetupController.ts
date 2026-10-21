import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\SetupController::storeAccount
* @see app/Http/Controllers/SetupController.php:116
* @route '/setup/accounts'
*/
export const storeAccount = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeAccount.url(options),
    method: 'post',
})

storeAccount.definition = {
    methods: ["post"],
    url: '/setup/accounts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SetupController::storeAccount
* @see app/Http/Controllers/SetupController.php:116
* @route '/setup/accounts'
*/
storeAccount.url = (options?: RouteQueryOptions) => {
    return storeAccount.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::storeAccount
* @see app/Http/Controllers/SetupController.php:116
* @route '/setup/accounts'
*/
storeAccount.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeAccount.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::storeAccount
* @see app/Http/Controllers/SetupController.php:116
* @route '/setup/accounts'
*/
const storeAccountForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeAccount.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::storeAccount
* @see app/Http/Controllers/SetupController.php:116
* @route '/setup/accounts'
*/
storeAccountForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeAccount.url(options),
    method: 'post',
})

storeAccount.form = storeAccountForm

/**
* @see \App\Http\Controllers\SetupController::updateAccount
* @see app/Http/Controllers/SetupController.php:134
* @route '/setup/accounts/{account}'
*/
export const updateAccount = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateAccount.url(args, options),
    method: 'put',
})

updateAccount.definition = {
    methods: ["put"],
    url: '/setup/accounts/{account}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SetupController::updateAccount
* @see app/Http/Controllers/SetupController.php:134
* @route '/setup/accounts/{account}'
*/
updateAccount.url = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { account: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { account: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            account: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        account: typeof args.account === 'object'
        ? args.account.id
        : args.account,
    }

    return updateAccount.definition.url
            .replace('{account}', parsedArgs.account.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::updateAccount
* @see app/Http/Controllers/SetupController.php:134
* @route '/setup/accounts/{account}'
*/
updateAccount.put = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateAccount.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SetupController::updateAccount
* @see app/Http/Controllers/SetupController.php:134
* @route '/setup/accounts/{account}'
*/
const updateAccountForm = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateAccount.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::updateAccount
* @see app/Http/Controllers/SetupController.php:134
* @route '/setup/accounts/{account}'
*/
updateAccountForm.put = (args: { account: number | { id: number } } | [account: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateAccount.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateAccount.form = updateAccountForm

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
* @see \App\Http\Controllers\SetupController::storeParty
* @see app/Http/Controllers/SetupController.php:190
* @route '/setup/parties'
*/
export const storeParty = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeParty.url(options),
    method: 'post',
})

storeParty.definition = {
    methods: ["post"],
    url: '/setup/parties',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SetupController::storeParty
* @see app/Http/Controllers/SetupController.php:190
* @route '/setup/parties'
*/
storeParty.url = (options?: RouteQueryOptions) => {
    return storeParty.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::storeParty
* @see app/Http/Controllers/SetupController.php:190
* @route '/setup/parties'
*/
storeParty.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeParty.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::storeParty
* @see app/Http/Controllers/SetupController.php:190
* @route '/setup/parties'
*/
const storePartyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeParty.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::storeParty
* @see app/Http/Controllers/SetupController.php:190
* @route '/setup/parties'
*/
storePartyForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeParty.url(options),
    method: 'post',
})

storeParty.form = storePartyForm

/**
* @see \App\Http\Controllers\SetupController::updateParty
* @see app/Http/Controllers/SetupController.php:207
* @route '/setup/parties/{party}'
*/
export const updateParty = (args: { party: number | { id: number } } | [party: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateParty.url(args, options),
    method: 'put',
})

updateParty.definition = {
    methods: ["put"],
    url: '/setup/parties/{party}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SetupController::updateParty
* @see app/Http/Controllers/SetupController.php:207
* @route '/setup/parties/{party}'
*/
updateParty.url = (args: { party: number | { id: number } } | [party: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { party: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { party: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            party: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        party: typeof args.party === 'object'
        ? args.party.id
        : args.party,
    }

    return updateParty.definition.url
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::updateParty
* @see app/Http/Controllers/SetupController.php:207
* @route '/setup/parties/{party}'
*/
updateParty.put = (args: { party: number | { id: number } } | [party: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateParty.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SetupController::updateParty
* @see app/Http/Controllers/SetupController.php:207
* @route '/setup/parties/{party}'
*/
const updatePartyForm = (args: { party: number | { id: number } } | [party: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateParty.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::updateParty
* @see app/Http/Controllers/SetupController.php:207
* @route '/setup/parties/{party}'
*/
updatePartyForm.put = (args: { party: number | { id: number } } | [party: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateParty.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateParty.form = updatePartyForm

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
* @see \App\Http\Controllers\SetupController::storeUser
* @see app/Http/Controllers/SetupController.php:47
* @route '/setup/users'
*/
export const storeUser = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeUser.url(options),
    method: 'post',
})

storeUser.definition = {
    methods: ["post"],
    url: '/setup/users',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SetupController::storeUser
* @see app/Http/Controllers/SetupController.php:47
* @route '/setup/users'
*/
storeUser.url = (options?: RouteQueryOptions) => {
    return storeUser.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::storeUser
* @see app/Http/Controllers/SetupController.php:47
* @route '/setup/users'
*/
storeUser.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeUser.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::storeUser
* @see app/Http/Controllers/SetupController.php:47
* @route '/setup/users'
*/
const storeUserForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeUser.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::storeUser
* @see app/Http/Controllers/SetupController.php:47
* @route '/setup/users'
*/
storeUserForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeUser.url(options),
    method: 'post',
})

storeUser.form = storeUserForm

/**
* @see \App\Http\Controllers\SetupController::updateUser
* @see app/Http/Controllers/SetupController.php:63
* @route '/setup/users/{user}'
*/
export const updateUser = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateUser.url(args, options),
    method: 'put',
})

updateUser.definition = {
    methods: ["put"],
    url: '/setup/users/{user}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SetupController::updateUser
* @see app/Http/Controllers/SetupController.php:63
* @route '/setup/users/{user}'
*/
updateUser.url = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { user: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { user: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            user: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        user: typeof args.user === 'object'
        ? args.user.id
        : args.user,
    }

    return updateUser.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::updateUser
* @see app/Http/Controllers/SetupController.php:63
* @route '/setup/users/{user}'
*/
updateUser.put = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateUser.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SetupController::updateUser
* @see app/Http/Controllers/SetupController.php:63
* @route '/setup/users/{user}'
*/
const updateUserForm = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateUser.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::updateUser
* @see app/Http/Controllers/SetupController.php:63
* @route '/setup/users/{user}'
*/
updateUserForm.put = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateUser.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateUser.form = updateUserForm

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
* @see \App\Http\Controllers\SetupController::lockPeriod
* @see app/Http/Controllers/SetupController.php:235
* @route '/setup/periods'
*/
export const lockPeriod = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lockPeriod.url(options),
    method: 'post',
})

lockPeriod.definition = {
    methods: ["post"],
    url: '/setup/periods',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SetupController::lockPeriod
* @see app/Http/Controllers/SetupController.php:235
* @route '/setup/periods'
*/
lockPeriod.url = (options?: RouteQueryOptions) => {
    return lockPeriod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::lockPeriod
* @see app/Http/Controllers/SetupController.php:235
* @route '/setup/periods'
*/
lockPeriod.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lockPeriod.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::lockPeriod
* @see app/Http/Controllers/SetupController.php:235
* @route '/setup/periods'
*/
const lockPeriodForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lockPeriod.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::lockPeriod
* @see app/Http/Controllers/SetupController.php:235
* @route '/setup/periods'
*/
lockPeriodForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lockPeriod.url(options),
    method: 'post',
})

lockPeriod.form = lockPeriodForm

/**
* @see \App\Http\Controllers\SetupController::unlockPeriod
* @see app/Http/Controllers/SetupController.php:251
* @route '/setup/periods/{lock}'
*/
export const unlockPeriod = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: unlockPeriod.url(args, options),
    method: 'delete',
})

unlockPeriod.definition = {
    methods: ["delete"],
    url: '/setup/periods/{lock}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SetupController::unlockPeriod
* @see app/Http/Controllers/SetupController.php:251
* @route '/setup/periods/{lock}'
*/
unlockPeriod.url = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { lock: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { lock: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            lock: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        lock: typeof args.lock === 'object'
        ? args.lock.id
        : args.lock,
    }

    return unlockPeriod.definition.url
            .replace('{lock}', parsedArgs.lock.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::unlockPeriod
* @see app/Http/Controllers/SetupController.php:251
* @route '/setup/periods/{lock}'
*/
unlockPeriod.delete = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: unlockPeriod.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\SetupController::unlockPeriod
* @see app/Http/Controllers/SetupController.php:251
* @route '/setup/periods/{lock}'
*/
const unlockPeriodForm = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlockPeriod.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::unlockPeriod
* @see app/Http/Controllers/SetupController.php:251
* @route '/setup/periods/{lock}'
*/
unlockPeriodForm.delete = (args: { lock: number | { id: number } } | [lock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlockPeriod.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

unlockPeriod.form = unlockPeriodForm

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

const SetupController = { accounts, storeAccount, updateAccount, parties, storeParty, updateParty, users, storeUser, updateUser, periods, lockPeriod, unlockPeriod, audit }

export default SetupController