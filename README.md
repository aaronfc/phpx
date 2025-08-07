# PHPX

## TODO
- [ ] Error: We need to use absolute path for static references. For instance:
```
<span class="stat-value"><?=$this->raw(DateHelper::formatWithTooltip($this->user->created_at, $this->user->getTimezone(), 'F j, Y'))?></span>
```
Will not work even though we have a `use` statement for `DateHelper` at the top. Whereas:
```
<span class="stat-value"><?=$this->raw(\Aaron\Cronpop\DateHelper::formatWithTooltip($this->user->created_at, $this->user->getTimezone(), 'F j, Y'))?></span>
```
Will work
- [ ] Review code after making it available for composer – used in cronpop.
- [ ] Properties on Component layout need to be set as protected. Private properties are not accessible when evaling.
- [ ] Component with childs work fine. But using a component as a layout feels odd. Maybe we need some "childs" rendering. Or placeholder/slots. See how we are using Layout and HomePage in cronpop.
- [ ] Improve error handling/reporting for evaled code.
- [ ] Performance improvements:
    - not running reflection every time. at compile time?
    - maybe even caching full resulting rendered HTML if depdendencies not change. at compile time?
