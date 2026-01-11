# Sentinel

Sentinel is a local, AI-assisted development tool that helps you evolve codebases safely and deliberately.

It works at the project and file level, using AI to propose reviewable patches based on your instructions and selected
context, then applies changes only when you approve them. No hidden execution, no silent overwrites, no magic.

Sentinel acts as a watchful collaborator: it understands your architecture, respects your constraints, and stays firmly
under your control while accelerating real development work.

### Documentation

- [Product specifications](PRODUCT.md)
- [Installation instructions](INSTALL.md)

### Testing

To run the test suite, use:

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test -n
vendor/bin/phpunit
```
