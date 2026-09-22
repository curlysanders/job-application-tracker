import { startStimulusApp } from '@symfony/stimulus-bundle';
import ContactCollectionController from './controllers/contact_collection_controller.js';

const app = startStimulusApp();
app.register('contact-collection', ContactCollectionController);
