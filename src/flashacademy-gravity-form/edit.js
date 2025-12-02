import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	Button,
	Notice,
	Spinner,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function Edit( { attributes, setAttributes } ) {
	const { formId = 0 } = attributes;

	const [ showPreview, setShowPreview ] = useState( false );
	const [ forms, setForms ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ loadError, setLoadError ] = useState( '' );

	const blockProps = useBlockProps();

	// Fetch available Gravity Forms for the dropdown.
	useEffect( () => {
		setIsLoading( true );
		setLoadError( '' );

		apiFetch( { path: '/flashacademy/v1/gravity-forms' } )
			.then( ( data ) => {
				if ( Array.isArray( data ) ) {
					setForms( data );
				} else {
					setForms( [] );
				}
			} )
			.catch( () => {
				setLoadError(
					__(
						'Unable to load Gravity Forms. Check that Gravity Forms is active and you have permission to edit.',
						'flashacademy-exit-intent-modal'
					)
				);
			} )
			.finally( () => {
				setIsLoading( false );
			} );
	}, [] );

	// We already get { id, title } from gf-api.php.
	const normalizedForms = forms.map( ( form ) => ( {
		id: parseInt( form.id, 10 ) || 0,
		title: form.title || `Form #${ form.id }`,
	} ) );

	const formOptions = [
		{
			label: __(
				'Select a form',
				'flashacademy-exit-intent-modal'
			),
			value: '0',
		},
		...normalizedForms.map( ( form ) => ( {
			label: `${ form.id } \u2013 ${ form.title }`,
			value: String( form.id ),
		} ) ),
	];

	const handleFormChange = ( value ) => {
		const id = parseInt( value, 10 ) || 0;
		setAttributes( { formId: id } );
		setShowPreview( false );
	};

	const getSummaryText = () => {
		if ( ! formId ) {
			return __(
				'No Gravity Form selected.',
				'flashacademy-exit-intent-modal'
			);
		}

		const found = normalizedForms.find( ( f ) => f.id === formId );

		if ( found ) {
			return sprintf(
				__(
					'Gravity Form #%1$d: %2$s (preview disabled)',
					'flashacademy-exit-intent-modal'
				),
				found.id,
				found.title
			);
		}

		return sprintf(
			__(
				'Gravity Form #%d (preview disabled)',
				'flashacademy-exit-intent-modal'
			),
			formId
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Gravity Form Settings',
						'flashacademy-exit-intent-modal'
					) }
					initialOpen={ true }
				>
					{ isLoading && <Spinner /> }

					{ loadError && (
						<Notice status="error" isDismissible={ false }>
							{ loadError }
						</Notice>
					) }

					{ ! isLoading && ! loadError && (
						<SelectControl
							label={ __(
								'Gravity Form',
								'flashacademy-exit-intent-modal'
							) }
							value={ String( formId || 0 ) }
							options={ formOptions }
							onChange={ handleFormChange }
						/>
					) }

					<Notice status="info" isDismissible={ false }>
						{ __(
							'Click "Preview Form" to render the real Gravity Form output. This may be slow.',
							'flashacademy-exit-intent-modal'
						) }
					</Notice>

					<Button
						variant={ showPreview ? 'secondary' : 'primary' }
						onClick={ () =>
							setShowPreview( ( prev ) => ! prev )
						}
						disabled={ ! formId }
					>
						{ showPreview
							? __(
									'Hide Preview',
									'flashacademy-exit-intent-modal'
							  )
							: __(
									'Preview Form',
									'flashacademy-exit-intent-modal'
							  ) }
					</Button>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ showPreview && formId ? (
					<ServerSideRender
						block="flashacademy/gravity-form"
						attributes={ {
							formId: parseInt( formId, 10 ) || 0,
						} }
					/>
				) : (
					<p
						style={ {
							padding: '12px',
							border: '1px dashed #ccc',
						} }
					>
						{ getSummaryText() }
					</p>
				) }
			</div>
		</>
	);
}
