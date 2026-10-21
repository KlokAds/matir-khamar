import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:145
* @route '/centers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/centers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:145
* @route '/centers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:145
* @route '/centers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:145
* @route '/centers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:145
* @route '/centers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:145
* @route '/centers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:145
* @route '/centers'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:159
* @route '/centers/{center}'
*/
export const show = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/centers/{center}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:159
* @route '/centers/{center}'
*/
show.url = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { center: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { center: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            center: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        center: typeof args.center === 'object'
        ? args.center.id
        : args.center,
    }

    return show.definition.url
            .replace('{center}', parsedArgs.center.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:159
* @route '/centers/{center}'
*/
show.get = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:159
* @route '/centers/{center}'
*/
show.head = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:159
* @route '/centers/{center}'
*/
const showForm = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:159
* @route '/centers/{center}'
*/
showForm.get = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:159
* @route '/centers/{center}'
*/
showForm.head = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:207
* @route '/setup/centers'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/setup/centers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:207
* @route '/setup/centers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:207
* @route '/setup/centers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:207
* @route '/setup/centers'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::store
* @see app/Http/Controllers/CostCenterController.php:207
* @route '/setup/centers'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:216
* @route '/setup/centers/{center}'
*/
export const update = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/setup/centers/{center}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:216
* @route '/setup/centers/{center}'
*/
update.url = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { center: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { center: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            center: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        center: typeof args.center === 'object'
        ? args.center.id
        : args.center,
    }

    return update.definition.url
            .replace('{center}', parsedArgs.center.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:216
* @route '/setup/centers/{center}'
*/
update.put = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:216
* @route '/setup/centers/{center}'
*/
const updateForm = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::update
* @see app/Http/Controllers/CostCenterController.php:216
* @route '/setup/centers/{center}'
*/
updateForm.put = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\CostCenterController::manage
* @see app/Http/Controllers/CostCenterController.php:34
* @route '/setup/centers'
*/
export const manage = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(options),
    method: 'get',
})

manage.definition = {
    methods: ["get","head"],
    url: '/setup/centers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CostCenterController::manage
* @see app/Http/Controllers/CostCenterController.php:34
* @route '/setup/centers'
*/
manage.url = (options?: RouteQueryOptions) => {
    return manage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::manage
* @see app/Http/Controllers/CostCenterController.php:34
* @route '/setup/centers'
*/
manage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::manage
* @see app/Http/Controllers/CostCenterController.php:34
* @route '/setup/centers'
*/
manage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: manage.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CostCenterController::manage
* @see app/Http/Controllers/CostCenterController.php:34
* @route '/setup/centers'
*/
const manageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::manage
* @see app/Http/Controllers/CostCenterController.php:34
* @route '/setup/centers'
*/
manageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::manage
* @see app/Http/Controllers/CostCenterController.php:34
* @route '/setup/centers'
*/
manageForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

manage.form = manageForm

/**
* @see \App\Http\Controllers\CostCenterController::toggle
* @see app/Http/Controllers/CostCenterController.php:74
* @route '/setup/centers/{center}/toggle'
*/
export const toggle = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggle.url(args, options),
    method: 'post',
})

toggle.definition = {
    methods: ["post"],
    url: '/setup/centers/{center}/toggle',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CostCenterController::toggle
* @see app/Http/Controllers/CostCenterController.php:74
* @route '/setup/centers/{center}/toggle'
*/
toggle.url = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { center: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { center: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            center: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        center: typeof args.center === 'object'
        ? args.center.id
        : args.center,
    }

    return toggle.definition.url
            .replace('{center}', parsedArgs.center.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::toggle
* @see app/Http/Controllers/CostCenterController.php:74
* @route '/setup/centers/{center}/toggle'
*/
toggle.post = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggle.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::toggle
* @see app/Http/Controllers/CostCenterController.php:74
* @route '/setup/centers/{center}/toggle'
*/
const toggleForm = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: toggle.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::toggle
* @see app/Http/Controllers/CostCenterController.php:74
* @route '/setup/centers/{center}/toggle'
*/
toggleForm.post = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: toggle.url(args, options),
    method: 'post',
})

toggle.form = toggleForm

/**
* @see \App\Http\Controllers\CostCenterController::move
* @see app/Http/Controllers/CostCenterController.php:91
* @route '/setup/centers/{center}/move'
*/
export const move = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: move.url(args, options),
    method: 'post',
})

move.definition = {
    methods: ["post"],
    url: '/setup/centers/{center}/move',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CostCenterController::move
* @see app/Http/Controllers/CostCenterController.php:91
* @route '/setup/centers/{center}/move'
*/
move.url = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { center: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { center: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            center: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        center: typeof args.center === 'object'
        ? args.center.id
        : args.center,
    }

    return move.definition.url
            .replace('{center}', parsedArgs.center.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::move
* @see app/Http/Controllers/CostCenterController.php:91
* @route '/setup/centers/{center}/move'
*/
move.post = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: move.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::move
* @see app/Http/Controllers/CostCenterController.php:91
* @route '/setup/centers/{center}/move'
*/
const moveForm = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: move.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::move
* @see app/Http/Controllers/CostCenterController.php:91
* @route '/setup/centers/{center}/move'
*/
moveForm.post = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: move.url(args, options),
    method: 'post',
})

move.form = moveForm

/**
* @see \App\Http\Controllers\CostCenterController::destroy
* @see app/Http/Controllers/CostCenterController.php:115
* @route '/setup/centers/{center}'
*/
export const destroy = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/setup/centers/{center}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\CostCenterController::destroy
* @see app/Http/Controllers/CostCenterController.php:115
* @route '/setup/centers/{center}'
*/
destroy.url = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { center: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { center: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            center: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        center: typeof args.center === 'object'
        ? args.center.id
        : args.center,
    }

    return destroy.definition.url
            .replace('{center}', parsedArgs.center.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::destroy
* @see app/Http/Controllers/CostCenterController.php:115
* @route '/setup/centers/{center}'
*/
destroy.delete = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\CostCenterController::destroy
* @see app/Http/Controllers/CostCenterController.php:115
* @route '/setup/centers/{center}'
*/
const destroyForm = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CostCenterController::destroy
* @see app/Http/Controllers/CostCenterController.php:115
* @route '/setup/centers/{center}'
*/
destroyForm.delete = (args: { center: number | { id: number } } | [center: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const CostCenterController = { index, show, store, update, manage, toggle, move, destroy }

export default CostCenterController