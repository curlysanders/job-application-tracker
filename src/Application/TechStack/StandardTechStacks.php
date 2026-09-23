<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\TechStack;

final class StandardTechStacks
{
    /** @return array<string, list<string>> */
    public static function all(): array
    {
        return [
            'Backend' => ['PHP', 'Symfony', 'Laravel', 'Node.js', 'Java', '.NET', 'Python', 'Django'],
            'Frontend' => ['JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular', 'HTML', 'CSS', 'Tailwind CSS'],
            'DevOps/Cloud' => ['Docker', 'Kubernetes', 'Terraform', 'GitHub Actions', 'GitLab CI', 'AWS', 'Azure', 'GCP'],
            'Testing' => ['PHPUnit', 'Pest', 'Behat', 'Cypress', 'Playwright'],
            'Queuing & Messaging' => ['RabbitMQ', 'Apache Kafka', 'ActiveMQ', 'NATS', 'AWS SQS', 'AWS SNS', 'Google Cloud Pub/Sub', 'Azure Service Bus'],
            'OS & Platforms' => ['Linux', 'Windows', 'macOS', 'Android', 'iOS'],
            'API & Integration' => ['REST', 'GraphQL', 'SOAP', 'API Platform', 'OpenAPI', 'JSON:API', 'gRPC', 'AsyncAPI'],
            'Relational Databases' => ['MariaDB', 'MySQL', 'PostgreSQL', 'Microsoft SQL Server', 'Oracle Database', 'SQLite'],
            'NoSQL Databases' => ['MongoDB', 'Amazon DynamoDB', 'Apache Cassandra', 'Couchbase', 'Neo4j'],
            'Search & Caching' => ['Redis', 'Memcached', 'Elasticsearch', 'OpenSearch'],
            'Architecture & Patterns' => [
                'Domain-Driven Design (DDD)',
                'Hexagonal Architecture (Ports & Adapters)',
                'Clean Architecture',
                'Onion Architecture',
                'Layered Architecture',
                'Command Query Responsibility Segregation (CQRS)',
                'Event Sourcing',
                'Event-Driven Architecture (EDA)',
                'Model-View-Controller (MVC)',
                'Modular Monolith',
                'Monolith',
                'Microservices',
                'Service-Oriented Architecture (SOA)',
                'Micro-frontends',
                'Serverless Architecture',
                'Actor Model',
                'Space-Based Architecture',
            ],
        ];
    }
}
