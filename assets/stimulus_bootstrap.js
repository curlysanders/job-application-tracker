import { startStimulusApp } from '@symfony/stimulus-bundle';
import ContactCollectionController from './controllers/contact_collection_controller.js';
import SourceUrlCollectionController from './controllers/source_url_collection_controller.js';
import TechStackCollectionController from './controllers/tech_stack_collection_controller.js';
import VacancyAuthoringController from './controllers/vacancy_authoring_controller.js';

const app = startStimulusApp();
app.register('contact-collection', ContactCollectionController);
app.register('source-url-collection', SourceUrlCollectionController);
app.register('tech-stack-collection', TechStackCollectionController);
app.register('vacancy-authoring', VacancyAuthoringController);
