@if(isset($paginator) && $paginator->hasPages())
    <div class="pagination">
        {{ $paginator->links() }}
    </div>
@endif
