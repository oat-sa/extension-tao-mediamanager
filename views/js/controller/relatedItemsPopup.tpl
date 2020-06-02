{{__ 'This'}} "{{name}}" {{__ 'is currently used in'}} {{number}} {{__ 'item(s)'}}:
<ul>
{{#each items}}<li>{{this.label}}</li>{{/each}}
</ul>
{{__ 'Are you sure you want to delete this'}} "{{name}}"?