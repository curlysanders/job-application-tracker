import { startStimulusApp } from '@symfony/stimulus-bundle';
import ContactCollectionController from './controllers/contact_collection_controller.js';
import TechStackCollectionController from './controllers/tech_stack_collection_controller.js';

const app = startStimulusApp();
app.register('contact-collection', ContactCollectionController);
app.register('tech-stack-collection', TechStackCollectionController);
