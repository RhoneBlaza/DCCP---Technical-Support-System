<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoriesController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $search = $request->string('search')->trim()->toString();

        $roots = Category::query()
            ->with('children')
            ->whereNull('parent_id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('name', $search)
                        ->orWhereHas('children', fn ($c) => $c->whereLike('name', $search));
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.categories.index', ['roots' => $roots, 'search' => $search]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $this->assertNoGrandchild($request);

        $category = Category::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        $this->audit->log('category_created', $category, 'Category created: '.$category->name);

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        if ($category->parent_id !== null && (int) $request->input('parent_id') === $category->id) {
            throw ValidationException::withMessages(['parent_id' => 'A category cannot be its own parent.']);
        }

        $this->assertNoGrandchild($request);

        $old = $category->only(['parent_id', 'name', 'description', 'sort_order', 'is_active']);

        $category->update($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        $this->audit->log('category_updated', $category, 'Category updated: '.$category->name, $old, $category->only(['parent_id', 'name', 'description', 'sort_order', 'is_active']));

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function toggleActive(Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        if ($category->is_active && $category->tickets()->exists()) {
            return back()->withErrors(['error' => 'Categories already referenced by tickets cannot be deactivated.']);
        }

        foreach ($category->children as $child) {
            if ($category->is_active && $child->tickets()->exists()) {
                return back()->withErrors(['error' => 'Categories already referenced by tickets cannot be deactivated.']);
            }
        }

        $category->update(['is_active' => ! $category->is_active]);

        $this->audit->log('category_status_changed', $category, 'Category activation state changed: '.$category->name);

        return back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        // Check if category has children
        if ($category->children()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete category that has subcategories.']);
        }

        // Check if category has tickets
        if ($category->tickets()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete category that has tickets associated.']);
        }

        $this->audit->log('category_deleted', $category, 'Category deleted: '.$category->name);

        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }

    /**
     * Categories have at most two levels: a parent may never itself have a parent.
     */
    protected function assertNoGrandchild(StoreCategoryRequest $request): void
    {
        $parentId = $request->input('parent_id');

        if ($parentId !== null) {
            $parent = Category::find($parentId);

            if ($parent !== null && $parent->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A category may only have one level of children.',
                ]);
            }
        }
    }
}
