import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DiaryController::storeNote
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
export const storeNote = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeNote.url(options),
    method: 'post',
})

storeNote.definition = {
    methods: ["post"],
    url: '/diary/notes',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DiaryController::storeNote
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
storeNote.url = (options?: RouteQueryOptions) => {
    return storeNote.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::storeNote
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
storeNote.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeNote.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::storeNote
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
const storeNoteForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeNote.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::storeNote
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
storeNoteForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeNote.url(options),
    method: 'post',
})

storeNote.form = storeNoteForm

/**
* @see \App\Http\Controllers\DiaryController::updateNote
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
export const updateNote = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateNote.url(args, options),
    method: 'put',
})

updateNote.definition = {
    methods: ["put"],
    url: '/diary/notes/{note}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DiaryController::updateNote
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
updateNote.url = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { note: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { note: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            note: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        note: typeof args.note === 'object'
        ? args.note.id
        : args.note,
    }

    return updateNote.definition.url
            .replace('{note}', parsedArgs.note.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::updateNote
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
updateNote.put = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateNote.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DiaryController::updateNote
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
const updateNoteForm = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateNote.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::updateNote
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
updateNoteForm.put = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateNote.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateNote.form = updateNoteForm

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/diary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::index
* @see app/Http/Controllers/DiaryController.php:27
* @route '/diary'
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
* @see \App\Http\Controllers\DiaryController::file
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
export const file = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: file.url(args, options),
    method: 'get',
})

file.definition = {
    methods: ["get","head"],
    url: '/diary/files/{file}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DiaryController::file
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
file.url = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { file: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { file: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            file: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        file: typeof args.file === 'object'
        ? args.file.id
        : args.file,
    }

    return file.definition.url
            .replace('{file}', parsedArgs.file.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::file
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
file.get = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: file.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::file
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
file.head = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: file.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DiaryController::file
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
const fileForm = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: file.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::file
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
fileForm.get = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: file.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DiaryController::file
* @see app/Http/Controllers/DiaryController.php:95
* @route '/diary/files/{file}'
*/
fileForm.head = (args: { file: number | { id: number } } | [file: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: file.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

file.form = fileForm

/**
* @see \App\Http\Controllers\DiaryController::storeGoal
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
export const storeGoal = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeGoal.url(options),
    method: 'post',
})

storeGoal.definition = {
    methods: ["post"],
    url: '/diary/goals',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DiaryController::storeGoal
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
storeGoal.url = (options?: RouteQueryOptions) => {
    return storeGoal.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::storeGoal
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
storeGoal.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeGoal.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::storeGoal
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
const storeGoalForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeGoal.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::storeGoal
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
storeGoalForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeGoal.url(options),
    method: 'post',
})

storeGoal.form = storeGoalForm

/**
* @see \App\Http\Controllers\DiaryController::updateGoal
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
export const updateGoal = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateGoal.url(args, options),
    method: 'put',
})

updateGoal.definition = {
    methods: ["put"],
    url: '/diary/goals/{goal}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DiaryController::updateGoal
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
updateGoal.url = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { goal: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { goal: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            goal: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        goal: typeof args.goal === 'object'
        ? args.goal.id
        : args.goal,
    }

    return updateGoal.definition.url
            .replace('{goal}', parsedArgs.goal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::updateGoal
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
updateGoal.put = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateGoal.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DiaryController::updateGoal
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
const updateGoalForm = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateGoal.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::updateGoal
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
updateGoalForm.put = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateGoal.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateGoal.form = updateGoalForm

const DiaryController = { storeNote, updateNote, index, file, storeGoal, updateGoal }

export default DiaryController