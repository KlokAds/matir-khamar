import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EntryController::index
* @see app/Http/Controllers/EntryController.php:49
* @route '/entries'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/entries',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EntryController::index
* @see app/Http/Controllers/EntryController.php:49
* @route '/entries'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::index
* @see app/Http/Controllers/EntryController.php:49
* @route '/entries'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::index
* @see app/Http/Controllers/EntryController.php:49
* @route '/entries'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EntryController::index
* @see app/Http/Controllers/EntryController.php:49
* @route '/entries'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::index
* @see app/Http/Controllers/EntryController.php:49
* @route '/entries'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::index
* @see app/Http/Controllers/EntryController.php:49
* @route '/entries'
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
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:97
* @route '/entries/{entry}'
*/
export const show = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/entries/{entry}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:97
* @route '/entries/{entry}'
*/
show.url = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entry: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { entry: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            entry: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        entry: typeof args.entry === 'object'
        ? args.entry.id
        : args.entry,
    }

    return show.definition.url
            .replace('{entry}', parsedArgs.entry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:97
* @route '/entries/{entry}'
*/
show.get = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:97
* @route '/entries/{entry}'
*/
show.head = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:97
* @route '/entries/{entry}'
*/
const showForm = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:97
* @route '/entries/{entry}'
*/
showForm.get = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::show
* @see app/Http/Controllers/EntryController.php:97
* @route '/entries/{entry}'
*/
showForm.head = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\EntryController::create
* @see app/Http/Controllers/EntryController.php:136
* @route '/entries/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/entries/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EntryController::create
* @see app/Http/Controllers/EntryController.php:136
* @route '/entries/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::create
* @see app/Http/Controllers/EntryController.php:136
* @route '/entries/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::create
* @see app/Http/Controllers/EntryController.php:136
* @route '/entries/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EntryController::create
* @see app/Http/Controllers/EntryController.php:136
* @route '/entries/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::create
* @see app/Http/Controllers/EntryController.php:136
* @route '/entries/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EntryController::create
* @see app/Http/Controllers/EntryController.php:136
* @route '/entries/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\EntryController::store
* @see app/Http/Controllers/EntryController.php:157
* @route '/entries'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/entries',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EntryController::store
* @see app/Http/Controllers/EntryController.php:157
* @route '/entries'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::store
* @see app/Http/Controllers/EntryController.php:157
* @route '/entries'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EntryController::store
* @see app/Http/Controllers/EntryController.php:157
* @route '/entries'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EntryController::store
* @see app/Http/Controllers/EntryController.php:157
* @route '/entries'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\EntryController::attach
* @see app/Http/Controllers/EntryController.php:363
* @route '/entries/{entry}/attachments'
*/
export const attach = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: attach.url(args, options),
    method: 'post',
})

attach.definition = {
    methods: ["post"],
    url: '/entries/{entry}/attachments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EntryController::attach
* @see app/Http/Controllers/EntryController.php:363
* @route '/entries/{entry}/attachments'
*/
attach.url = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entry: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { entry: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            entry: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        entry: typeof args.entry === 'object'
        ? args.entry.id
        : args.entry,
    }

    return attach.definition.url
            .replace('{entry}', parsedArgs.entry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::attach
* @see app/Http/Controllers/EntryController.php:363
* @route '/entries/{entry}/attachments'
*/
attach.post = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: attach.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EntryController::attach
* @see app/Http/Controllers/EntryController.php:363
* @route '/entries/{entry}/attachments'
*/
const attachForm = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: attach.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EntryController::attach
* @see app/Http/Controllers/EntryController.php:363
* @route '/entries/{entry}/attachments'
*/
attachForm.post = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: attach.url(args, options),
    method: 'post',
})

attach.form = attachForm

/**
* @see \App\Http\Controllers\EntryController::reverse
* @see app/Http/Controllers/EntryController.php:349
* @route '/entries/{entry}/reverse'
*/
export const reverse = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverse.url(args, options),
    method: 'post',
})

reverse.definition = {
    methods: ["post"],
    url: '/entries/{entry}/reverse',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EntryController::reverse
* @see app/Http/Controllers/EntryController.php:349
* @route '/entries/{entry}/reverse'
*/
reverse.url = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entry: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { entry: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            entry: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        entry: typeof args.entry === 'object'
        ? args.entry.id
        : args.entry,
    }

    return reverse.definition.url
            .replace('{entry}', parsedArgs.entry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EntryController::reverse
* @see app/Http/Controllers/EntryController.php:349
* @route '/entries/{entry}/reverse'
*/
reverse.post = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reverse.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EntryController::reverse
* @see app/Http/Controllers/EntryController.php:349
* @route '/entries/{entry}/reverse'
*/
const reverseForm = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reverse.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EntryController::reverse
* @see app/Http/Controllers/EntryController.php:349
* @route '/entries/{entry}/reverse'
*/
reverseForm.post = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reverse.url(args, options),
    method: 'post',
})

reverse.form = reverseForm

const entries = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    attach: Object.assign(attach, attach),
    reverse: Object.assign(reverse, reverse),
}

export default entries