import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import Highlight from '@tiptap/extension-highlight';
import TextAlign from '@tiptap/extension-text-align';
import Placeholder from '@tiptap/extension-placeholder';
import CharacterCount from '@tiptap/extension-character-count';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ArrowCounterClockwise, ArrowClockwise, CheckCircle, DotsThree, FileImage, FilmSlate, Highlighter, LinkSimple, ListBullets, MagnifyingGlass, Rows, SquaresFour, TextAlignCenter, TextAlignLeft, TextB, TextHTwo, TextItalic, TextUnderline, UploadSimple, YoutubeLogo } from '@phosphor-icons/react';
import {
  BookOpen,
  Check,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Eye,
  EyeOff,
  Grid2X2,
  Home,
  Image,
  Images,
  LayoutDashboard,
  KeyRound,
  LogOut,
  Mail,
  MapPin,
  Newspaper,
  Package,
  Plus,
  Pencil,
  Save,
  Search,
  Share2,
  ShieldCheck,
  Sparkles,
  Settings,
  Trash2,
  Upload,
  Users,
  UserRound,
  Video,
  X,
  CircleAlert,
  Activity,
  BarChart3,
  Bot,
  Gauge,
  MousePointerClick,
  ShieldAlert,
  ChartNoAxesCombined,
} from 'lucide-react';

const csrf = document.querySelector('meta[name="csrf-token"]').content;

const homeChildren = [
  { key: 'home_hero', label: 'Sliders', icon: Images, section: 'hero' },
  { key: 'home_about', label: 'Nosotros en Inicio', icon: Users, section: 'about' },
  { key: 'home_catalog', label: 'Banner de catálogo', icon: BookOpen, section: 'cta' },
];

const productChildren = [
  { key: 'categories', label: 'Categorías', icon: Grid2X2, pageSlug: 'categorias' },
  { key: 'products', label: 'Productos', icon: Package, pageSlug: 'productos' },
];

const privateChildren = [
  { key: 'private_clients', label: 'Listado de clientes', icon: Users },
  { key: 'private_cart', label: 'Carrito y pedidos', icon: Package },
  { key: 'private_logistics', label: 'Logística · pedidos', icon: Rows },
  { key: 'private_stock', label: 'Logística · stock', icon: Grid2X2 },
  { key: 'private_accounting', label: 'Contabilidad · pedidos', icon: BookOpen },
  { key: 'private_billed', label: 'Facturado', icon: CheckCircle },
  { key: 'private_invoices', label: 'Facturas', icon: FileImage },
  { key: 'private_payments', label: 'Comprobantes de pago', icon: CheckCircle },
  { key: 'private_config', label: 'Configuración del portal', icon: Settings },
  { key: 'private_all_orders', label: 'Todos los pedidos', icon: Search },
  { key: 'private_exports', label: 'Exportación', icon: Upload },
];

const primaryNav = [
  { key: 'home', label: 'Inicio', icon: Home, children: homeChildren },
  { key: 'about_page', label: 'Nosotros', icon: Users, pageSlug: 'nosotros' },
  { key: 'products_parent', label: 'Productos', icon: Package, children: productChildren },
  { key: 'catalog', label: 'Catálogo', icon: BookOpen, pageSlug: 'catalogo' },
  { key: 'news', label: 'Novedades', icon: Newspaper, pageSlug: 'novedades' },
  { key: 'quality', label: 'Calidad', icon: ShieldCheck, pageSlug: 'calidad' },
  { key: 'stores', label: 'Dónde comprar', icon: MapPin },
  { key: 'contact', label: 'Contacto', icon: Mail },
  { key: 'private', label: 'Zona privada', icon: ShieldCheck, children: privateChildren },
];

function WhatsAppIcon({ size = 19, ...props }) {
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" {...props}><path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.4L3.5 20.5l1.5-4.3A8.5 8.5 0 1 1 20.5 11.6Z"/><path d="M9.1 8.7c.2-.5.5-.7.8-.7h.5c.2 0 .4.1.5.4l.7 1.6c.1.2 0 .5-.1.7l-.5.6a6.2 6.2 0 0 0 2.8 2.8l.6-.5c.2-.2.5-.2.7-.1l1.6.7c.3.1.4.3.4.5v.5c0 .3-.2.6-.7.8-.5.3-1.4.4-2.6-.1a9.4 9.4 0 0 1-4.8-4.8c-.5-1.2-.4-2.1-.1-2.4Z"/></svg>;
}

const utilityNav = [
  { key: 'newsletter', label: 'Newsletter', icon: Mail },
  { key: 'social', label: 'Redes sociales del footer', icon: Share2 },
  { key: 'seo', label: 'SEO de la web', icon: Search },
  { key: 'whatsapp', label: 'WhatsApp', icon: WhatsAppIcon },
  { key: 'users', label: 'Usuarios', icon: UserRound },
];

const sectionMeta = {
  hero: { label: 'Inicio', description: 'Sliders, mensajes principales y llamadas a la acción' },
  about: { label: 'Nosotros', description: 'Fotografía y presentación institucional' },
  about_page: { label: 'Nosotros', description: 'Historia institucional, fotografía principal y motivos para elegir Moldpack' },
  quality_page: { label: 'Calidad', description: 'Políticas, argumentos, fotografía y documento descargable' },
  catalog_page: { label: 'Catálogo', description: 'Portada, documento completo y descargas independientes por categoría' },
  products: { label: 'Productos', description: 'Productos destacados y categorías visibles' },
  cta: { label: 'Catálogo', description: 'Banner, texto y botón de descarga' },
  news: { label: 'Novedades', description: 'Artículos, categorías y archivo que alimentan la página pública y la portada' },
  categories: { label: 'Categorías', description: 'Accesos visuales del inicio' },
};

function RichEditor({ value, onChange }) {
  const editor = useEditor({
    extensions: [
      StarterKit,
      Link.configure({ openOnClick: false, autolink: true, defaultProtocol: 'https' }),
      Underline,
      Highlight.configure({ multicolor: false }),
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
      Placeholder.configure({ placeholder: 'Escribí un texto claro y útil para tus visitantes…' }),
      CharacterCount.configure({ limit: 1200 }),
    ],
    content: value || '',
    onUpdate: ({ editor: current }) => onChange(current.getHTML()),
  });

  if (!editor) return <div className="editor-skeleton" aria-label="Cargando editor" />;
  const tool = (label, icon, action, active = false, disabled = false) => <button type="button" title={label} aria-label={label} aria-pressed={active} disabled={disabled} className={active ? 'active' : ''} onClick={action}>{icon}</button>;
  const setLink = () => {
    const previous = editor.getAttributes('link').href;
    const url = window.prompt('Pegá el enlace completo', previous || 'https://');
    if (url === null) return;
    if (!url) editor.chain().focus().extendMarkRange('link').unsetLink().run();
    else editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
  };

  return (
    <div className="editor">
      <div className="editor-tools" role="toolbar" aria-label="Formato de texto">
        <div className="tool-group">{tool('Negrita', <TextB size={18} />, () => editor.chain().focus().toggleBold().run(), editor.isActive('bold'))}{tool('Cursiva', <TextItalic size={18} />, () => editor.chain().focus().toggleItalic().run(), editor.isActive('italic'))}{tool('Subrayado', <TextUnderline size={18} />, () => editor.chain().focus().toggleUnderline().run(), editor.isActive('underline'))}{tool('Resaltar', <Highlighter size={18} />, () => editor.chain().focus().toggleHighlight().run(), editor.isActive('highlight'))}</div>
        <div className="tool-group">{tool('Subtítulo', <TextHTwo size={18} />, () => editor.chain().focus().toggleHeading({ level: 2 }).run(), editor.isActive('heading', { level: 2 }))}{tool('Lista', <ListBullets size={18} />, () => editor.chain().focus().toggleBulletList().run(), editor.isActive('bulletList'))}{tool('Enlace', <LinkSimple size={18} />, setLink, editor.isActive('link'))}</div>
        <div className="tool-group">{tool('Alinear a la izquierda', <TextAlignLeft size={18} />, () => editor.chain().focus().setTextAlign('left').run(), editor.isActive({ textAlign: 'left' }))}{tool('Centrar', <TextAlignCenter size={18} />, () => editor.chain().focus().setTextAlign('center').run(), editor.isActive({ textAlign: 'center' }))}</div>
        <div className="tool-group history">{tool('Deshacer', <ArrowCounterClockwise size={18} />, () => editor.chain().focus().undo().run(), false, !editor.can().undo())}{tool('Rehacer', <ArrowClockwise size={18} />, () => editor.chain().focus().redo().run(), false, !editor.can().redo())}</div>
      </div>
      <EditorContent editor={editor} />
      <div className="editor-status"><span>Texto enriquecido</span><span>{editor.storage.characterCount.characters()} / 1200 caracteres</span></div>
    </div>
  );
}

function MediaForm({ ownerType, ownerId, recommendation, maxFiles = null, currentCount = 0 }) {
  const [data, setData] = useState({ kind: 'image', url: '', alt: '', caption: '', file: null });
  const [dragging, setDragging] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [uploadError, setUploadError] = useState('');
  const accept = data.kind === 'image' ? 'image/jpeg,image/png,image/webp,image/svg+xml' : 'video/mp4,video/webm';
  const chooseFile = (file) => {
    setUploadError('');
    if (!file) return;
    if (file.size > 100 * 1024 * 1024) return setUploadError('El archivo supera los 100 MB. Comprimilo o elegí una versión más liviana.');
    setData((current) => ({ ...current, file }));
  };

  const submit = (event) => {
    event.preventDefault();
    if (maxFiles && currentCount >= maxFiles) return setUploadError(`Ya cargaste los ${maxFiles} medios disponibles. Eliminá uno para reemplazarlo.`);
    const form = new FormData();
    Object.entries({ ...data, owner_type: ownerType, owner_id: ownerId }).forEach(([key, value]) => {
      if (value !== null) form.append(key, value);
    });
    router.post('/admin/media', form, { forceFormData: true, preserveScroll: true, onStart: () => { setUploading(true); setUploadError(''); }, onProgress: (event) => setProgress(event.percentage || 0), onError: (errors) => setUploadError(errors.file || errors.url || 'No se pudo subir el archivo. Verificá el formato e intentá otra vez.'), onSuccess: () => setData((current) => ({ ...current, file: null, url: '', alt: '' })), onFinish: () => { setUploading(false); setProgress(0); } });
  };

  return (
    <form className="media-form" onSubmit={submit}>
      {maxFiles && <div className="media-capacity"><strong>Galería del producto</strong><span>{currentCount} de {maxFiles} espacios ocupados</span></div>}
      <div className="media-guide"><FileImage size={20} /><div><strong>Archivo recomendado</strong><span>{recommendation}</span></div></div>
      <div className="media-kind-tabs" role="tablist" aria-label="Tipo de medio">
        {[['image', 'Imagen', FileImage], ['video', 'Video local', FilmSlate], ['youtube', 'YouTube', YoutubeLogo]].map(([kind, label, Icon]) => <button key={kind} type="button" className={data.kind === kind ? 'active' : ''} onClick={() => setData({ ...data, kind, file: null })}><Icon size={19} />{label}</button>)}
      </div>
      {data.kind === 'youtube'
        ? <label className="youtube-field">Enlace de YouTube<input type="url" placeholder="https://youtube.com/watch?v=..." value={data.url} onChange={(event) => setData({ ...data, url: event.target.value })} /><small className="field-help">Pegá el enlace normal. El CMS lo convierte en una inserción privada y segura.</small></label>
        : <label className={`upload-dropzone ${dragging ? 'dragging' : ''} ${data.file ? 'has-file' : ''}`} onDragEnter={(event) => { event.preventDefault(); setDragging(true); }} onDragOver={(event) => event.preventDefault()} onDragLeave={() => setDragging(false)} onDrop={(event) => { event.preventDefault(); setDragging(false); chooseFile(event.dataTransfer.files?.[0]); }}>
          <input type="file" accept={accept} onChange={(event) => chooseFile(event.target.files?.[0])} />
          <div className="upload-symbol">{data.file ? <CheckCircle size={27} weight="fill" /> : <UploadSimple size={27} />}</div>
          <div><strong>{data.file ? data.file.name : `Arrastrá ${data.kind === 'image' ? 'una imagen' : 'un video'} aquí`}</strong><span>{data.file ? `${(data.file.size / 1024 / 1024).toFixed(2)} MB · listo para subir` : 'o hacé clic para buscar en tu computadora'}</span></div>
          <b>{data.file ? 'Cambiar archivo' : 'Seleccionar archivo'}</b>
        </label>}
      <div className="media-meta-row"><label>Texto alternativo<input placeholder="Ej: Cupcakes presentados en packaging Moldpack" value={data.alt} onChange={(event) => setData({ ...data, alt: event.target.value })} /><small className="field-help">Describí lo que aparece en la imagen para accesibilidad y SEO.</small></label><button className="upload-action" disabled={uploading || (maxFiles && currentCount >= maxFiles) || (data.kind !== 'youtube' && !data.file)} type="submit">{uploading ? <><span className="button-loader" />Subiendo {progress ? `${progress}%` : '…'}</> : <><UploadSimple size={19} />Agregar medio</>}</button></div>
      {uploadError && <div className="upload-error" role="alert"><strong>No pudimos subir el medio</strong><span>{uploadError}</span></div>}
      {uploading && <div className="upload-progress"><span style={{ width: `${progress}%` }} /></div>}
    </form>
  );
}

function MediaList({ media = [] }) {
  if (!media.length) return <div className="empty-media">Todavía no hay medios cargados.</div>;

  return (
    <div className="media-list">
      {media.map((item) => (
        <div className="media-chip" key={item.id}>
          {item.kind === 'image' ? <img src={'/' + item.path} alt="" /> : <Video size={25} />}
          <div><strong>{item.alt || 'Medio sin descripción'}</strong><span>{item.kind}</span></div>
          <button type="button" aria-label="Eliminar medio" onClick={() => confirm('¿Eliminar este medio?') && router.delete('/admin/media/' + item.id, { preserveScroll: true })}><Trash2 size={16} /></button>
        </div>
      ))}
    </div>
  );
}

function CatalogDocumentForm({ ownerType, ownerId, settings = {}, label = 'Documento PDF' }) {
  const [file, setFile] = useState(null);
  const [dragging, setDragging] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');
  const submit = (event) => {
    event.preventDefault();
    if (!file) return setError('Seleccioná un archivo PDF.');
    const form = new FormData();
    form.append('owner_type', ownerType);
    form.append('owner_id', ownerId);
    form.append('file', file);
    router.post('/admin/documents', form, { forceFormData: true, preserveScroll: true, onStart: () => { setUploading(true); setError(''); }, onError: (errors) => setError(errors.file || 'No se pudo cargar el documento.'), onSuccess: () => setFile(null), onFinish: () => setUploading(false) });
  };
  return <form className="catalog-document-form" onSubmit={submit}>
    <div className="media-guide"><BookOpen size={20} /><div><strong>{label}</strong><span>PDF optimizado, hasta 50 MB. Verificá que abra correctamente y que el texto sea legible antes de publicarlo.</span></div></div>
    {settings.document_path && <div className="catalog-current-document"><FileImage size={20} /><div><strong>{settings.document_name || 'Documento actual'}</strong><span>{[settings.document_pages ? `${settings.document_pages} páginas` : null, settings.document_size ? `${(settings.document_size / 1048576).toFixed(1)} MB` : null].filter(Boolean).join(' · ') || 'Archivo vinculado'}</span></div><a href={'/' + settings.document_path} target="_blank" rel="noreferrer">Revisar PDF</a></div>}
    <label className={`upload-dropzone ${dragging ? 'dragging' : ''} ${file ? 'has-file' : ''}`} onDragEnter={(event) => { event.preventDefault(); setDragging(true); }} onDragOver={(event) => event.preventDefault()} onDragLeave={() => setDragging(false)} onDrop={(event) => { event.preventDefault(); setDragging(false); setFile(event.dataTransfer.files?.[0] || null); }}>
      <input type="file" accept="application/pdf" onChange={(event) => setFile(event.target.files?.[0] || null)} />
      <div className="upload-symbol">{file ? <CheckCircle size={27} weight="fill" /> : <UploadSimple size={27} />}</div>
      <div><strong>{file?.name || 'Arrastrá el PDF aquí'}</strong><span>{file ? `${(file.size / 1048576).toFixed(1)} MB · listo para subir` : 'o hacé clic para buscarlo en tu computadora'}</span></div><b>{file ? 'Cambiar PDF' : 'Seleccionar PDF'}</b>
    </label>
    {error && <div className="upload-error" role="alert"><strong>No pudimos cargar el documento</strong><span>{error}</span></div>}
    <button className="upload-action" type="submit" disabled={!file || uploading}>{uploading ? 'Subiendo…' : <><UploadSimple size={18} />{settings.document_path ? 'Reemplazar documento' : 'Publicar documento'}</>}</button>
  </form>;
}

function CatalogCategoryEditor({ item }) {
  const [data, setData] = useState({ ...item, settings: item.settings || {} });
  useEffect(() => setData({ ...item, settings: item.settings || {} }), [item]);
  return <article className="catalog-category-admin">
    <div className="form-grid"><label>Nombre público<input value={data.title || ''} onChange={(event) => setData({ ...data, title: event.target.value })} /></label><label>Texto de acción<input value={data.label || ''} onChange={(event) => setData({ ...data, label: event.target.value })} /></label></div>
    <div className="product-featured-control"><div><strong>Mostrar esta descarga</strong><span>Si se desactiva, la tarjeta desaparece de la página pública.</span></div><label className="apple-switch"><input type="checkbox" checked={!!data.is_visible} onChange={(event) => setData({ ...data, is_visible: event.target.checked })} /><span /><b>{data.is_visible ? 'Visible' : 'Oculta'}</b></label></div>
    <CatalogDocumentForm ownerType="item" ownerId={item.id} settings={data.settings} label={`PDF de ${data.title || 'la categoría'}`} />
    <div className="editor-actions"><button className="save" type="button" onClick={() => router.put('/admin/items/' + item.id, { ...data, is_visible: !!data.is_visible }, { preserveScroll: true })}><Save size={17} />Guardar categoría</button></div>
  </article>;
}

function CatalogPageEditor({ section, recommendation }) {
  const [data, setData] = useState({ ...section, settings: section.settings || {} });
  const [selectedId, setSelectedId] = useState(section.items?.[0]?.id || null);
  useEffect(() => setData({ ...section, settings: section.settings || {} }), [section]);
  const selected = data.items?.find((item) => item.id === selectedId);
  return <div className="module-content catalog-admin-module">
    <div className="module-intro premium"><div><span>Biblioteca comercial</span><h1>Catálogo de productos</h1><p>Administrá la portada, el PDF completo y las descargas por categoría desde un único lugar.</p></div><a className="primary" href="/catalogo" target="_blank" rel="noreferrer"><Eye size={18} />Ver página pública</a></div>
    <div className="catalog-admin-guide"><Sparkles size={21} /><div><strong>Guía de publicación</strong><p>1. Cargá una portada vertical nítida. 2. Subí el PDF completo y completá páginas/peso visible. 3. Revisá cada categoría y cargá su PDF. 4. Abrí la vista pública y probá todas las descargas.</p></div></div>
    <article className="content-panel elevated"><div className="panel-heading"><div><span>Catálogo principal</span><h2>Presentación y descarga general</h2></div><label className="publish"><input type="checkbox" checked={!!data.is_visible} onChange={(event) => setData({ ...data, is_visible: event.target.checked })} />Visible</label></div><div className="panel-body">
      <div className="form-grid"><label className="wide">Título principal<input value={data.title || ''} onChange={(event) => setData({ ...data, title: event.target.value })} /><small className="field-help">En el diseño aparece como “Catálogo completo”.</small></label><label className="wide">Descripción<RichEditor value={data.body} onChange={(body) => setData({ ...data, body })} /></label><div className="document-auto wide"><div><span>Páginas automáticas</span><strong>{data.settings.document_pages || '—'}</strong><small>Se calcula cuando subís o reemplazás el PDF.</small></div><div><span>Peso automático</span><strong>{data.settings.document_size ? `${(data.settings.document_size / 1048576).toFixed(1)} MB` : '—'}</strong><small>Sale del archivo real cargado en el CMS.</small></div></div><label className="wide">Título de categorías<input value={data.settings.categories_title || ''} placeholder="¿Buscás solo una categoría?" onChange={(event) => setData({ ...data, settings: { ...data.settings, categories_title: event.target.value } })} /></label></div>
      <div className="media-block"><h4>Imágenes de la portada</h4><div className="catalog-image-order"><div><b>01</b><span><strong>Imagen principal</strong><small>Se recorta dentro del marco vertical de 234×333 px y recibe una capa negra al 24%.</small></span></div><div><b>02</b><span><strong>Logo Moldpack</strong><small>Aparece superpuesto en la esquina inferior derecha de la portada.</small></span></div></div><div className="product-image-advice"><div><strong>234 × 333</strong><span>Proporción exacta 26/37 en escritorio.</span></div><div><strong>Orden importante</strong><span>Primero portada y debajo el logo.</span></div><div><strong>Recorte Figma</strong><span>181,624% × 127,628%, sin deformar el marco.</span></div></div><MediaList media={data.media} /><MediaForm ownerType="section" ownerId={section.id} recommendation={recommendation} maxFiles={2} currentCount={data.media?.length || 0} /></div>
      <CatalogDocumentForm ownerType="section" ownerId={section.id} settings={data.settings} label="Catálogo completo" />
      <div className="editor-actions"><button className="save" type="button" onClick={() => router.put('/admin/sections/' + section.id, { ...data, is_visible: !!data.is_visible }, { preserveScroll: true })}><Save size={17} />Guardar presentación</button></div>
    </div></article>
    <div className="catalog-category-layout"><section className="catalog-category-index"><header><span>Descargas específicas</span><h2>Categorías</h2><p>Elegí una tarjeta para editar su nombre, visibilidad y PDF.</p></header>{data.items?.map((item) => <button type="button" key={item.id} className={selectedId === item.id ? 'active' : ''} onClick={() => setSelectedId(item.id)}><BookOpen size={18} /><span><strong>{item.title}</strong><small>{item.settings?.document_path ? 'PDF cargado' : 'Falta cargar PDF'}</small></span><ChevronRight size={16} /></button>)}</section>{selected && <CatalogCategoryEditor key={selected.id} item={selected} />}</div>
  </div>;
}

const htmlText = (value = '') => value.replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').trim();
const paragraphHtml = (value = '') => `<p>${value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n+/g, '<br>')}</p>`;

function QualityPageEditor({ section, recommendation }) {
  const [data, setData] = useState({ ...section, settings: section.settings || {} });
  const [points, setPoints] = useState((section.items || []).map(item => ({ ...item, copy: htmlText(item.body) })));
  useEffect(() => { setData({ ...section, settings: section.settings || {} }); setPoints((section.items || []).map(item => ({ ...item, copy: htmlText(item.body) }))); }, [section]);
  const photo = data.media?.[0];
  const savePresentation = () => router.put('/admin/sections/' + section.id, { ...data, body: paragraphHtml(htmlText(data.body)), is_visible: !!data.is_visible }, { preserveScroll: true });
  const savePoint = (point) => router.put('/admin/items/' + point.id, { ...point, body: paragraphHtml(point.copy), is_visible: !!point.is_visible }, { preserveScroll: true });
  const updatePoint = (id, patch) => setPoints(current => current.map(point => point.id === id ? { ...point, ...patch } : point));

  return <div className="module-content quality-admin">
    <div className="module-intro premium"><div><span>Página institucional</span><h1>Calidad</h1><p>Completá la página siguiendo el orden en que la verá el visitante.</p></div><a className="primary" href="/calidad" target="_blank" rel="noreferrer"><Eye size={18}/>Ver página pública</a></div>
    <div className="quality-admin-status"><div><ShieldCheck size={20}/><span><strong>Página conectada</strong><small>Los cambios guardados se publican directamente en /calidad.</small></span></div><label className="apple-switch"><input type="checkbox" checked={!!data.is_visible} onChange={event=>setData({...data,is_visible:event.target.checked})}/><span/><b>{data.is_visible?'Visible':'Oculta'}</b></label></div>
    <div className="quality-admin-layout">
      <div className="quality-admin-fields">
        <section className="quality-step"><header><span>1</span><div><h2>Encabezado</h2><p>El título y el texto introductorio que aparecen a la izquierda.</p></div></header><div className="quality-step-body"><label>Título principal<input value={data.title||''} onChange={event=>setData({...data,title:event.target.value})}/></label><label>Introducción<textarea rows="4" value={htmlText(data.body)} onChange={event=>setData({...data,body:paragraphHtml(event.target.value)})}/><small>Incluye la mención de los certificados R.N.E. y R.N.P.A.</small></label><button className="quality-save" type="button" onClick={savePresentation}><Save size={16}/>Guardar encabezado</button></div></section>
        <section className="quality-step"><header><span>2</span><div><h2>Políticas de calidad</h2><p>Cada punto tiene un título breve y una explicación. Se muestran con el check rosa.</p></div></header><div className="quality-policies-admin">{points.map((point,index)=><article key={point.id}><div className="quality-policy-number"><img src="/assets/figma/exact/calidad/check-circle.svg" alt=""/><span>Política {index+1}</span></div><label>Título<input value={point.title||''} onChange={event=>updatePoint(point.id,{title:event.target.value})}/></label><label>Descripción<textarea rows="3" value={point.copy} onChange={event=>updatePoint(point.id,{copy:event.target.value})}/></label><div><label className="quality-visible"><input type="checkbox" checked={!!point.is_visible} onChange={event=>updatePoint(point.id,{is_visible:event.target.checked})}/>Mostrar este punto</label><button type="button" onClick={()=>savePoint(point)}><Save size={15}/>Guardar política</button></div></article>)}</div></section>
        <section className="quality-step"><header><span>3</span><div><h2>Fotografía principal</h2><p>Usá una imagen horizontal de 1366×430 px para conservar el recorte de Figma.</p></div></header><div className="quality-step-body"><MediaList media={data.media}/><MediaForm ownerType="section" ownerId={section.id} recommendation={recommendation}/></div></section>
        <section className="quality-step"><header><span>4</span><div><h2>Documento descargable</h2><p>Definí el nombre visible y cargá el PDF de la política.</p></div></header><div className="quality-step-body quality-document-fields"><label>Título de la sección<input value={data.settings.downloads_title||''} onChange={event=>setData({...data,settings:{...data.settings,downloads_title:event.target.value}})}/></label><label>Nombre visible del PDF<input value={data.settings.document_label||''} onChange={event=>setData({...data,settings:{...data.settings,document_label:event.target.value}})}/></label><button className="quality-save" type="button" onClick={savePresentation}><Save size={16}/>Guardar textos</button><CatalogDocumentForm ownerType="section" ownerId={section.id} settings={data.settings} label="Política de calidad"/></div></section>
      </div>
      <aside className="quality-admin-preview"><header><div><Eye size={16}/><strong>Vista previa</strong></div><span>Escritorio</span></header><div className="quality-mini-page"><div className="quality-mini-copy"><h3>{data.title}</h3><p>{htmlText(data.body)}</p>{points.filter(point=>point.is_visible).map(point=><div key={point.id}><img src="/assets/figma/exact/calidad/check-circle.svg" alt=""/><p><strong>{point.title}:</strong> {point.copy}</p></div>)}</div><div className="quality-mini-photo">{photo?.path?<img src={'/'+photo.path} alt=""/>:<Image size={28}/>}</div></div><footer><strong>{data.settings.downloads_title||'Descargas'}</strong><span>{data.settings.document_label||'Política de calidad'}</span></footer></aside>
    </div>
  </div>;
}

function youtubeEmbed(url = '') {
  const match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([^&?/]+)/i);
  return match ? `https://www.youtube-nocookie.com/embed/${match[1]}?rel=0&modestbranding=1` : '';
}

function ContentPreview({ data, isSlide }) {
  const media = data.media?.[0];
  const source = media?.kind === 'youtube' ? youtubeEmbed(media.url) : media?.path ? `/${media.path}` : '';
  return <section className="content-preview" aria-label="Vista previa del contenido">
    <div className="preview-top"><div><Eye size={16} /><strong>Vista previa</strong><span>Así se verá aproximadamente en el sitio.</span></div><a href="/" target="_blank" rel="noreferrer">Abrir sitio <ChevronRight size={14} /></a></div>
    <div className={`preview-canvas ${isSlide ? 'hero' : ''}`}>
      <div className="preview-media">
        {media?.kind === 'image' && source && <img src={source} alt={media.alt || data.title || ''} />}
        {media?.kind === 'video' && source && <video src={source} muted loop autoPlay playsInline />}
        {media?.kind === 'youtube' && source && <iframe src={source} title={media.alt || data.title || 'Video'} allowFullScreen />}
        {!source && <div className="preview-empty"><Image size={25} /><span>Agregá un medio para completar la vista previa</span></div>}
      </div>
      <div className="preview-scrim" />
      <div className="preview-copy"><span>Vista de escritorio</span><h3>{data.title || 'Título del contenido'}</h3><div className="preview-rich" dangerouslySetInnerHTML={{ __html: data.body || '<p>El texto descriptivo aparecerá en este espacio.</p>' }} />{data.label && <b>{data.label}</b>}</div>
    </div>
  </section>;
}

function EditorialGuide({ isSlide, recommendation }) {
  return <aside className="editor-guide" aria-label="Recomendaciones de contenido">
    <div className="guide-title"><Sparkles size={18} /><div><strong>Recomendaciones para publicar</strong><span>Una guía rápida para lograr mejores resultados.</span></div></div>
    <div className="guide-grid">
      <div><span>01</span><strong>{isSlide ? 'Título breve' : 'Mensaje directo'}</strong><p>{isSlide ? 'Ideal entre 5 y 9 palabras. Evitá saltos de línea manuales.' : 'Usá un título descriptivo que se entienda fuera de contexto.'}</p></div>
      <div><span>02</span><strong>Texto escaneable</strong><p>Una idea por párrafo, frases cortas y enlaces con nombres descriptivos.</p></div>
      <div><span>03</span><strong>Medio optimizado</strong><p>{recommendation}</p></div>
    </div>
  </aside>;
}

function RelatedProductPicker({ products = [], currentId, selected = [], source = 'automatic', onChange, onAutomatic }) {
  const [query, setQuery] = useState('');
  const normalized = query.trim().toLowerCase();
  const results = products.filter((product) => product.id !== currentId && [product.title, product.settings?.brand, product.settings?.code].some((value) => (value || '').toLowerCase().includes(normalized)));
  const toggle = (id) => {
    if (selected.includes(id)) return onChange(selected.filter((value) => value !== id), true);
    if (selected.length < 4) onChange([...selected, id], true);
  };
  return <section className="related-picker"><header><div><span>Venta cruzada</span><h4>Productos relacionados</h4><p>{source === 'automatic' ? 'Selección inteligente según familia, categoría, nombre y código.' : 'Selección manual. Podés agregar o quitar hasta completar 4 productos.'}</p></div><b className={source === 'automatic' ? 'smart' : ''}>{source === 'automatic' ? <><Sparkles size={13} /> Automático</> : `${selected.length}/4 manuales`}</b></header>{source !== 'automatic' && <button type="button" className="related-automatic" onClick={onAutomatic}><Sparkles size={15} />Volver a selección inteligente</button>}<label className="related-search"><Search size={17} /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Buscar por producto, marca o código…" /></label><div className="related-options">{results.map((product) => { const checked = selected.includes(product.id); return <label key={product.id} className={checked ? 'selected' : ''}><input type="checkbox" checked={checked} disabled={!checked && selected.length >= 4} onChange={() => toggle(product.id)} />{product.media?.[0]?.kind === 'image' ? <img src={'/' + product.media[0].path} onError={(event) => { event.currentTarget.src = '/assets/product-placeholder.svg'; }} alt="" /> : <img src="/assets/product-placeholder.svg" alt="" />}<span><strong>{product.title}</strong><small>{product.settings?.brand || 'Sin marca'} · {product.settings?.code || 'Sin código'}</small></span><Check size={17} /></label>})}{!results.length && <div className="related-empty">No encontramos productos con esa búsqueda.</div>}</div></section>;
}

function ItemEditor({ item, recommendation, variant = 'default', index, onClose, sectionType, catalogItems = [] }) {
  const [data, setData] = useState({ ...item, settings: item.settings || {} });
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState('');
  useEffect(() => setData({ ...item, settings: item.settings || {} }), [item]);
  const isSlide = variant === 'slide';
  const isBenefit = sectionType === 'about_page';
  const isProduct = sectionType === 'products';
  const isNews = sectionType === 'news';
  const save = () => router.put('/admin/items/' + item.id, { ...data, is_visible: !!data.is_visible }, { preserveScroll: true, onStart: () => { setSaving(true); setSaveError(''); }, onError: () => setSaveError('Revisá los campos marcados e intentá nuevamente.'), onFinish: () => setSaving(false) });

  return (
    <article className={'item-editor admin-surface-enter ' + (isSlide ? 'slide-editor detail-editor' : '')}>
      <header className="item-editor-head">
        <div className="item-number">{String(index + 1).padStart(2, '0')}</div>
        <div><span>{isSlide ? 'Slide' : 'Elemento'}</span><h3>{data.title || 'Sin título'}</h3></div>
        <button type="button" className="visibility" onClick={() => setData({ ...data, is_visible: !data.is_visible })}>
          {data.is_visible ? <><Eye size={17} />Visible</> : <><EyeOff size={17} />Oculto</>}
        </button>
        {onClose
          ? <button type="button" className="icon" aria-label="Cerrar editor" onClick={onClose}><ChevronRight size={18} /></button>
          : <button type="button" className="icon danger" aria-label="Eliminar" onClick={() => confirm('¿Eliminar este elemento?') && router.delete('/admin/items/' + item.id)}><Trash2 size={18} /></button>}
      </header>

      <div className="item-editor-body">
        {!isBenefit && <ContentPreview data={data} isSlide={isSlide} />}
        {isBenefit
          ? <aside className="editor-guide"><div className="guide-title"><Sparkles size={18} /><div><strong>Tarjeta de valor</strong><span>Este contenido aparece en “¿Por qué elegirnos?”.</span></div></div><div className="guide-grid"><div><span>01</span><strong>Título concreto</strong><p>Usá entre 2 y 4 palabras para conservar la jerarquía del diseño.</p></div><div><span>02</span><strong>Una sola idea</strong><p>Explicá el beneficio en una frase breve, clara y verificable.</p></div><div><span>03</span><strong>Icono del sistema</strong><p>El icono se asigna automáticamente para mantener el Figma intacto.</p></div></div></aside>
          : <EditorialGuide isSlide={isSlide} recommendation={recommendation} />}
        <div className="form-grid">
          <label className="wide">{isSlide ? 'Título principal' : 'Título'}<input maxLength="180" value={data.title || ''} onChange={(event) => setData({ ...data, title: event.target.value })} /><small className="field-help">Es lo primero que leerá la persona. {(data.title || '').length}/180 caracteres.</small></label>
          {!isSlide && !isBenefit && !isNews && <label>{sectionType === 'products' ? 'Categoría principal' : 'Subtítulo'}<input value={sectionType === 'products' ? (data.settings.category || '') : (data.subtitle || '')} onChange={(event) => { if (sectionType !== 'products') return setData({ ...data, subtitle: event.target.value }); const category = event.target.value; const family = data.settings.subcategory || data.settings.family || ''; setData({ ...data, subtitle: [category, family].filter(Boolean).join(' / '), settings: { ...data.settings, category } }); }} /><small className="field-help">{sectionType === 'products' ? 'Define el grupo principal del filtro, por ejemplo: Soportes o Cajas.' : 'Complementa el título sin repetirlo.'}</small></label>}
          {!isSlide && !isBenefit && <label>{sectionType === 'products' ? 'Presentaciones' : isNews ? 'Categoría de la novedad' : 'Etiqueta'}<input value={data.label || ''} onChange={(event) => setData({ ...data, label: event.target.value })} /><small className="field-help">{sectionType === 'products' ? 'Ingresá sólo el detalle, por ejemplo: TRI0001 U/10. El sitio agrega “PRESENTACIONES:” automáticamente.' : isNews ? 'Se muestra arriba del título y alimenta el filtro lateral. Ejemplo: Productos, Empresa, Calidad o Nuevos ingresos.' : 'Categoría corta, por ejemplo: Productos.'}</small></label>}
          {isProduct && <div className="product-featured-control wide"><div><strong>Destacado en home</strong><span>Controla si este producto aparece en “Productos destacados” de Inicio.</span></div><label className="apple-switch"><input type="checkbox" checked={!!data.settings.featured_home} onChange={(event) => setData({ ...data, settings: { ...data.settings, featured_home: event.target.checked } })} aria-label="Destacado en home" /><span aria-hidden="true"/><b>{data.settings.featured_home ? 'Activado' : 'Desactivado'}</b></label></div>}
          {isProduct && <><label>Marca<input value={data.settings.brand || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, brand: event.target.value } })} /><small className="field-help">Marca o línea comercial del producto.</small></label><label>Código<input value={data.settings.code || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, code: event.target.value } })} /><small className="field-help">Código interno o SKU utilizado para identificarlo.</small></label></>}
          {isProduct && <div className="product-fields wide"><label>Subcategoría o familia<input value={data.settings.subcategory || data.settings.family || ''} onChange={(event) => { const family = event.target.value; const category = data.settings.category || ''; setData({ ...data, subtitle: [category, family].filter(Boolean).join(' / '), settings: { ...data.settings, family, subcategory: family } }); }} /><small className="field-help">Crea automáticamente el nivel interno del filtro, por ejemplo: Motivos o Metalizados.</small></label><label>Descripción de diseño<textarea rows="3" value={data.settings.design || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, design: event.target.value } })} /></label><label>Envase apto para alimentos<textarea rows="3" value={data.settings.food_safe || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, food_safe: event.target.value } })} /></label><label>Calidad<textarea rows="3" value={data.settings.quality || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, quality: event.target.value } })} /></label></div>}
          <label className="wide">{isSlide ? 'Texto descriptivo' : 'Contenido'}<RichEditor value={data.body} onChange={(body) => setData({ ...data, body })} /><small className="field-help">Podés usar subtítulos, listas, enlaces y resaltados. Conservá una jerarquía simple.</small></label>
          {isSlide && <label>Texto del botón<input maxLength="40" value={data.label || ''} onChange={(event) => setData({ ...data, label: event.target.value })} /><small className="field-help">Acción concreta de 2 o 3 palabras.</small></label>}
          {!isBenefit && !isProduct && !isNews && <label>{isSlide ? 'Destino del botón' : 'Enlace'}<input value={data.url || ''} onChange={(event) => setData({ ...data, url: event.target.value })} /><small className="field-help">Usá una ruta interna (#productos) o una URL completa.</small></label>}
          {isNews && <div className="product-featured-control wide"><div><strong>Detalle automático</strong><span>El botón “Leer más” abre la nota pública. La ruta se genera al guardar y no hace falta cargar enlaces técnicos.</span></div><span className="auto-route-preview">{data.settings?.slug ? `/novedades/${data.settings.slug}` : 'Se genera al guardar'}</span></div>}
        </div>
        {!isBenefit && <div className="media-block"><h4>{isProduct ? 'Galería del producto' : isNews ? 'Imagen de portada de la novedad' : 'Medio visual'}</h4>{isProduct && <div className="product-image-advice"><div><strong>Hasta 3 medios</strong><span>Combiná imágenes, un video local o un video de YouTube.</span></div><div><strong>1200 × 1200 px</strong><span>Para imágenes, usá WebP o PNG de hasta 2 MB.</span></div><div><strong>Producto centrado</strong><span>Dejá 8–10% de aire alrededor y evitá texto o recortes.</span></div></div>}{isNews && <div className="product-image-advice"><div><strong>784 × 700 px</strong><span>Es la proporción exacta de la tarjeta pública.</span></div><div><strong>Sin texto crítico</strong><span>El hover oscurece la foto y muestra un + al centro.</span></div><div><strong>Recorte natural</strong><span>Usá JPG/WebP liviano, producto centrado y buena luz.</span></div></div>}<MediaList media={data.media} /><MediaForm ownerType="item" ownerId={item.id} recommendation={recommendation} maxFiles={isProduct ? 3 : null} currentCount={data.media?.length || 0} /></div>}
        {isProduct && <RelatedProductPicker products={catalogItems} currentId={item.id} selected={data.settings.related_ids || []} source={data.settings.related_source || (data.settings.related_manual ? 'manual' : 'automatic')} onChange={(related_ids) => setData({ ...data, settings: { ...data.settings, related_ids, related_manual: true, related_source: 'manual' } })} onAutomatic={() => setData({ ...data, settings: { ...data.settings, related_ids: [], related_manual: false, related_source: 'automatic' } })} />}
        {saveError && <p className="save-error" role="alert">{saveError}</p>}
        <div className="editor-actions split"><button className="delete-action" type="button" onClick={() => confirm('¿Eliminar este elemento? Esta acción no se puede deshacer.') && router.delete('/admin/items/' + item.id)}><Trash2 size={16} />Eliminar</button><button className="save" type="button" disabled={saving} onClick={save}>{saving ? <><span className="button-loader" />Guardando…</> : <><Save size={17} />Guardar {isSlide ? 'slide' : 'elemento'}</>}</button></div>
      </div>
    </article>
  );
}

function ItemIndex({ items = [], selectedId, onSelect, onCreate, label = 'elementos', pageSize = 0, featuredSearch = false }) {
  const [query, setQuery] = useState('');
  const [view, setView] = useState('rows');
  const [page, setPage] = useState(1);
  const normalized = query.trim().toLocaleLowerCase('es');
  const filtered = items.filter((item) => [item.title, item.subtitle, item.label, item.settings?.category, item.settings?.subcategory, item.settings?.family, item.settings?.brand, item.settings?.code, ...(item.settings?.codes || [])].some((value) => String(value || '').toLocaleLowerCase('es').includes(normalized)));
  const pages = pageSize ? Math.max(1, Math.ceil(filtered.length / pageSize)) : 1;
  const currentPage = Math.min(page, pages);
  const visible = pageSize ? filtered.slice((currentPage - 1) * pageSize, currentPage * pageSize) : filtered;
  useEffect(() => setPage(1), [query, items.length]);

  return <section className={`content-index ${featuredSearch ? 'catalog-index' : ''}`}>
    {featuredSearch && <div className="catalog-search-intro"><div><Sparkles size={18} /><span>Buscador principal</span></div><strong>Encontrá cualquier producto en segundos</strong><p>Buscá por nombre, categoría, familia, presentación, marca o código.</p></div>}
    <header className="index-toolbar">
      <div className="index-search"><MagnifyingGlass size={featuredSearch ? 21 : 18} /><input aria-label={`Buscar ${label}`} placeholder={featuredSearch ? 'Buscar por nombre, categoría, marca o código…' : `Buscar ${label}...`} value={query} onChange={(event) => setQuery(event.target.value)} />{query && <button type="button" onClick={() => setQuery('')}>Limpiar</button>}</div>
      <div className="view-switch" aria-label="Cambiar vista"><button className={view === 'rows' ? 'active' : ''} type="button" onClick={() => setView('rows')}><Rows size={17} /></button><button className={view === 'grid' ? 'active' : ''} type="button" onClick={() => setView('grid')}><SquaresFour size={17} /></button></div>
      <button className="primary" type="button" onClick={onCreate}><Plus size={17} />Nuevo {featuredSearch ? 'producto' : ''}</button>
    </header>
    {pageSize > 0 && <div className="index-results"><span><strong>{filtered.length}</strong> productos encontrados</span><span>Mostrando {filtered.length ? (currentPage - 1) * pageSize + 1 : 0}–{Math.min(currentPage * pageSize, filtered.length)} de {filtered.length}</span></div>}
    <div className={`index-list ${view}`}>
      {visible.map((item, index) => <button key={item.id} type="button" className={`index-row ${selectedId === item.id ? 'selected' : ''}`} onClick={() => onSelect(item.id)}>
        <span className="index-number">{String((currentPage - 1) * (pageSize || visible.length) + index + 1).padStart(2, '0')}</span>
        {item.media?.[0]?.kind === 'image' ? <img src={'/' + item.media[0].path} onError={(event) => { event.currentTarget.src = '/assets/product-placeholder.svg'; }} alt="" /> : <div className="index-placeholder"><Image size={19} /></div>}
        <span className="index-copy"><strong>{item.title || 'Sin título'}</strong><small>{item.is_visible ? 'Publicado' : 'Oculto'} · {item.media?.length || 0} medios</small></span>
        <span className={`status-dot ${item.is_visible ? 'live' : ''}`}>{item.is_visible ? 'Visible' : 'Oculto'}</span>
        <DotsThree size={20} />
      </button>)}
      {!filtered.length && <div className="index-empty"><Search size={24} /><strong>Sin resultados</strong><span>Probá con otro término de búsqueda.</span></div>}
    </div>
    {pageSize > 0 && pages > 1 && <nav className="index-pagination" aria-label="Paginación de productos"><button type="button" disabled={currentPage === 1} onClick={() => setPage(currentPage - 1)}><ChevronLeft size={16} />Anterior</button><div>{Array.from({ length: pages }, (_, index) => index + 1).filter((number) => number === 1 || number === pages || Math.abs(number - currentPage) <= 1).map((number, index, shown) => <React.Fragment key={number}>{index > 0 && number - shown[index - 1] > 1 && <span>…</span>}<button type="button" className={number === currentPage ? 'active' : ''} aria-current={number === currentPage ? 'page' : undefined} onClick={() => setPage(number)}>{number}</button></React.Fragment>)}</div><button type="button" disabled={currentPage === pages} onClick={() => setPage(currentPage + 1)}>Siguiente<ChevronRight size={16} /></button></nav>}
  </section>;
}

function SectionEditor({ section, recommendations }) {
  const [data, setData] = useState({ ...section, settings: section.settings || {} });
  const isHero = data.type === 'hero';
  const hasItems = ['categories', 'products', 'news', 'about_page', 'quality_page'].includes(data.type);
  const recommendation = recommendations[data.type] || recommendations.media;
  const [selectedItemId, setSelectedItemId] = useState(data.items?.[0]?.id || null);
  const selectedItem = data.items?.find((item) => item.id === selectedItemId);
  const save = () => router.put('/admin/sections/' + section.id, { ...data, is_visible: !!data.is_visible }, { preserveScroll: true });
  useEffect(() => {
    setData({ ...section, settings: section.settings || {} });
    if (selectedItemId && !section.items?.some((item) => item.id === selectedItemId)) {
      setSelectedItemId(section.items?.[0]?.id || null);
    }
  }, [section]);
  const createItem = () => router.post('/admin/sections/' + section.id + '/items', { title: data.type === 'about_page' ? 'Nueva razón' : data.type === 'products' ? 'Nuevo producto' : data.type === 'news' ? 'Nueva novedad' : 'Nuevo elemento' }, { preserveScroll: true, onSuccess: (response) => { const refreshed = response.props.pages?.flatMap((page) => page.sections || []).find((candidate) => candidate.id === section.id); const newest = refreshed?.items?.reduce((latest, item) => !latest || item.id > latest.id ? item : latest, null); if (newest) setSelectedItemId(newest.id); } });

  if (data.type === 'catalog_page') return <CatalogPageEditor section={section} recommendation={recommendation} />;
  if (data.type === 'quality_page') return <QualityPageEditor section={section} recommendation={recommendation} />;

  if (isHero) {
    return (
      <div className="module-content">
        <div className="module-intro index-intro"><div><span>Inicio</span><h1>Sliders principales</h1><p>Ordená, buscá y editá el contenido principal sin perder la visión general.</p></div><div className="index-summary"><strong>{data.items?.length || 0}</strong><span>slides</span></div></div>
        <div className="workflow-strip"><div className="done"><span>1</span><p><strong>Elegí un banner</strong><small>Seleccioná uno del índice o creá uno nuevo.</small></p></div><ChevronRight size={16} /><div><span>2</span><p><strong>Editá y revisá</strong><small>Completá el contenido usando la vista previa.</small></p></div><ChevronRight size={16} /><div><span>3</span><p><strong>Publicá</strong><small>Guardá los cambios y verificá el sitio.</small></p></div></div>
        <div className="section-state compact"><label><input type="checkbox" checked={!!data.is_visible} onChange={(event) => setData({ ...data, is_visible: event.target.checked })} />Sliders publicados</label><button className="small ghost" type="button" onClick={save}><Save size={16} />Guardar estado</button></div>
        <div className={`index-workspace ${selectedItem ? 'editing' : ''}`}>
          <ItemIndex items={data.items} selectedId={selectedItemId} onSelect={setSelectedItemId} label="slides" onCreate={() => router.post('/admin/sections/' + section.id + '/items', { title: 'Nuevo slide' })} />
          {selectedItem && <ItemEditor key={selectedItem.id} item={selectedItem} index={data.items.findIndex((item) => item.id === selectedItem.id)} variant="slide" recommendation={recommendation} onClose={() => setSelectedItemId(null)} />}
        </div>
      </div>
    );
  }

  return (
    <div className="module-content">
      <div className="module-intro">
        <div><span>Sitio web</span><h1>{sectionMeta[data.type]?.label || data.title}</h1><p>{sectionMeta[data.type]?.description}</p></div>
      </div>
      <article className="content-panel">
        <div className="panel-heading"><div><span>{data.type === 'products' ? 'Presentación en la portada' : 'Contenido de la sección'}</span><h2>{data.type === 'products' ? 'Título del bloque en Inicio' : (data.title || 'Sección sin título')}</h2></div><label className="publish"><input type="checkbox" checked={!!data.is_visible} onChange={(event) => setData({ ...data, is_visible: event.target.checked })} />Visible</label></div>
        <div className="panel-body">
          <div className="form-grid">
            <label className="wide">{data.type === 'products' ? 'Texto que aparece sobre los productos destacados en Inicio' : 'Título'}<input value={data.title || ''} onChange={(event) => setData({ ...data, title: event.target.value })} />{data.type === 'products' && <small className="field-help">Este título sólo encabeza el bloque de productos en la portada. No aparece dentro del catálogo general.</small>}</label>
            {['about', 'about_page', 'quality_page', 'cta', 'rich_text'].includes(data.type) && <label className="wide">{data.type === 'about_page' ? 'Primer bloque de texto' : data.type === 'quality_page' ? 'Introducción' : 'Texto'}<RichEditor value={data.body} onChange={(body) => setData({ ...data, body })} /></label>}
            {data.type === 'about_page' && <><label className="wide">Título del segundo bloque<input value={data.settings.second_title || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, second_title: event.target.value } })} /><small className="field-help">Se muestra debajo de la historia principal, junto a la misma fotografía.</small></label><label className="wide">Segundo bloque de texto<RichEditor value={data.settings.second_body || ''} onChange={(second_body) => setData({ ...data, settings: { ...data.settings, second_body } })} /></label><label className="wide">Título de las razones para elegirnos<input value={data.settings.benefits_title || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, benefits_title: event.target.value } })} /></label></>}
            {data.type === 'quality_page' && <><label>Título de descargas<input value={data.settings.downloads_title || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, downloads_title: event.target.value } })} /></label><label>Nombre del documento<input value={data.settings.document_label || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, document_label: event.target.value } })} /></label></>}
            {data.type === 'cta' && <><label>Texto del botón<input value={data.settings.button_label || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, button_label: event.target.value } })} /></label><label>Destino del botón<input value={data.settings.button_url || ''} onChange={(event) => setData({ ...data, settings: { ...data.settings, button_url: event.target.value } })} /></label></>}
          </div>

          {['about', 'about_page', 'quality_page', 'cta', 'rich_text', 'media'].includes(data.type) && <div className="media-block"><h4>{data.type === 'cta' ? 'Imagen del banner' : 'Fotografía principal'}</h4><MediaList media={data.media} /><MediaForm ownerType="section" ownerId={section.id} recommendation={recommendation} /></div>}
          {data.type === 'quality_page' && <CatalogDocumentForm ownerType="section" ownerId={section.id} settings={data.settings} label="Política de calidad" />}

          <div className="editor-actions"><button className="save" type="button" onClick={save}><Save size={17} />Guardar cambios</button></div>

          {hasItems && <div className="items"><div className="items-head"><div><span>Contenido relacionado</span><h3>{data.type === 'about_page' ? 'Razones para elegirnos' : `Índice de ${sectionMeta[data.type]?.label}`}</h3></div></div>{data.type === 'products' && <div className="product-index-guide"><Sparkles size={19} /><div><strong>Cómo administrar el catálogo</strong><p>Usá el buscador para encontrar un producto y abrilo para editar contenido, fotografías, estado en Inicio y relacionados. “Nuevo producto” crea una ficha con la misma guía editorial y recomendaciones de imagen.</p></div></div>}<div className={`index-workspace ${selectedItem ? 'editing' : ''}`}><ItemIndex items={data.items} selectedId={selectedItemId} onSelect={setSelectedItemId} label={data.type === 'about_page' ? 'razones' : data.type === 'products' ? 'productos' : 'elementos'} pageSize={data.type === 'products' ? 30 : 0} featuredSearch={data.type === 'products'} onCreate={createItem} />{selectedItem && <ItemEditor key={selectedItem.id} item={selectedItem} index={data.items.findIndex((item) => item.id === selectedItem.id)} recommendation={recommendation} sectionType={data.type} catalogItems={data.items} onClose={() => setSelectedItemId(null)} />}</div></div>}
        </div>
      </article>
    </div>
  );
}

function PagePresenceSwitch({ page }) {
  const [enabled, setEnabled] = useState(!!page.show_on_home);
  const toggle = (next) => {
    setEnabled(next);
    router.put('/admin/pages/' + page.id, { ...page, show_on_home: next, is_published: !!page.is_published }, { preserveScroll: true });
  };
  return <div className="home-presence"><div className="presence-icon"><Home size={19} /></div><div><strong>Mostrar también en Inicio</strong><span>{enabled ? 'Esta sección aparece en su propia página y en la portada.' : 'Esta sección sólo aparece en su página independiente.'}</span><small>URL pública: /{page.slug}</small></div><label className="apple-switch"><input type="checkbox" checked={enabled} onChange={(event) => toggle(event.target.checked)} /><span aria-hidden="true" /><b>{enabled ? 'Activado' : 'Desactivado'}</b></label></div>;
}

function PageSettings({ page }) {
  const [data, setData] = useState(page);
  return <ModuleFrame eyebrow="Visibilidad orgánica" title="SEO de la web" description="Controlá cómo aparece Moldpack en buscadores y resultados compartidos."><article className="content-panel elevated"><div className="panel-heading"><div><span>Vista previa</span><h2>Resultado de búsqueda</h2></div><div className="module-badge"><Search size={15} />Indexación</div></div><div className="panel-body"><div className="seo-preview"><span>moldpack.com.ar</span><strong>{data.seo_title || data.name}</strong><p>{data.seo_description || 'Agregá una descripción clara de la página.'}</p></div><div className="form-grid"><label>Nombre interno<input value={data.name} onChange={(event) => setData({ ...data, name: event.target.value })} /></label><label>Dirección de la página<input value={data.slug} onChange={(event) => setData({ ...data, slug: event.target.value })} /></label><label className="wide">Título SEO<input maxLength="160" value={data.seo_title || ''} onChange={(event) => setData({ ...data, seo_title: event.target.value })} /><small>{(data.seo_title || '').length}/160 caracteres</small></label><label className="wide">Descripción SEO<textarea maxLength="320" value={data.seo_description || ''} onChange={(event) => setData({ ...data, seo_description: event.target.value })} /><small>{(data.seo_description || '').length}/320 caracteres</small></label></div><div className="editor-actions"><button className="save" type="button" onClick={() => router.put('/admin/pages/' + data.id, { ...data, is_published: !!data.is_published })}><Save size={17} />Guardar SEO</button></div></div></article></ModuleFrame>;
}

function SeoModule({ pages = [] }) {
  const [selectedId, setSelectedId] = useState(pages[0]?.id);
  const selected = pages.find((page) => page.id === selectedId) || pages[0];
  const [data, setData] = useState(selected || {});
  const [image, setImage] = useState(null);
  useEffect(() => { if (selected) { setData(selected); setImage(null); } }, [selectedId, selected?.updated_at]);
  if (!selected) return null;
  const save = () => router.put('/admin/pages/' + data.id, { ...data, is_published: !!data.is_published, noindex: !!data.noindex }, { preserveScroll: true });
  const uploadImage = () => {
    if (!image) return;
    const form = new FormData(); form.append('image', image);
    router.post(`/admin/pages/${data.id}/seo-image`, form, { forceFormData: true, preserveScroll: true });
  };
  const score = [data.seo_title?.length >= 35 && data.seo_title?.length <= 60, data.seo_description?.length >= 120 && data.seo_description?.length <= 160, !!data.canonical_url, !!(data.seo_image || selected.seo_image)].filter(Boolean).length;
  return <ModuleFrame eyebrow="Posicionamiento orgánico" title="SEO por sección" description="Controlá cómo aparece cada página en Google, WhatsApp y redes sociales.">
    <div className="seo-admin-layout">
      <aside className="seo-page-index content-panel elevated"><div className="panel-heading"><div><span>Páginas</span><h2>Elegí una sección</h2></div></div>{pages.map((page) => <button type="button" key={page.id} className={page.id === selectedId ? 'active' : ''} onClick={() => setSelectedId(page.id)}><span><strong>{page.name}</strong><small>/{page.slug === 'inicio' ? '' : page.slug}</small></span><b>{page.seo_title && page.seo_description ? 'Configurado' : 'Pendiente'}</b></button>)}</aside>
      <div className="seo-workspace">
        <article className="seo-guide"><Sparkles size={20}/><div><strong>Guía para un SEO profesional</strong><p>Usá un título de 35 a 60 caracteres con la búsqueda principal. La descripción debe tener entre 120 y 160 caracteres, explicar el beneficio y mencionar Moldpack de forma natural. Cada página debe tener textos únicos.</p></div><span>{score}/4</span></article>
        <article className="content-panel elevated"><div className="panel-heading"><div><span>Vista en Google</span><h2>{selected.name}</h2></div><div className="module-badge"><Search size={15}/>{score === 4 ? 'Listo' : 'En progreso'}</div></div><div className="panel-body">
          <div className="seo-preview"><span>{data.canonical_url || `https://moldpack.com.ar/${data.slug === 'inicio' ? '' : data.slug}`}</span><strong>{data.seo_title || `${data.name} | Moldpack`}</strong><p>{data.seo_description || 'Escribí una descripción clara y específica para esta página.'}</p></div>
          <div className="form-grid"><label className="wide">Título SEO<input maxLength="60" value={data.seo_title || ''} onChange={(event) => setData({ ...data, seo_title: event.target.value })}/><small className={(data.seo_title?.length || 0) > 60 ? 'field-error' : ''}>{(data.seo_title || '').length}/60 · ideal: 35 a 60</small></label><label className="wide">Descripción para buscadores<textarea maxLength="160" value={data.seo_description || ''} onChange={(event) => setData({ ...data, seo_description: event.target.value })}/><small>{(data.seo_description || '').length}/160 · ideal: 120 a 160</small></label><label className="wide">Palabras y temas principales<input value={data.seo_keywords || ''} placeholder="packaging gastronómico, pirotines, moldes para hornear" onChange={(event) => setData({ ...data, seo_keywords: event.target.value })}/><small>Separalos con comas. Sirven como guía editorial; no repitas palabras sin sentido.</small></label><label className="wide">URL canónica<input type="url" value={data.canonical_url || ''} placeholder={`https://moldpack.com.ar/${data.slug === 'inicio' ? '' : data.slug}`} onChange={(event) => setData({ ...data, canonical_url: event.target.value })}/></label></div>
          <div className="seo-social-fields"><h3>Vista al compartir</h3><div className="form-grid"><label>Título social<input maxLength="60" value={data.og_title || ''} placeholder={data.seo_title || data.name} onChange={(event) => setData({ ...data, og_title: event.target.value })}/></label><label>Descripción social<input maxLength="160" value={data.og_description || ''} placeholder={data.seo_description || ''} onChange={(event) => setData({ ...data, og_description: event.target.value })}/></label></div><div className="seo-image-control"><div className="seo-image-preview"><img src={'/' + (data.seo_image || 'assets/figma/exact/logo-header.png')} alt="Vista previa SEO"/></div><label><strong>Imagen para Google y redes</strong><span>1200×630 px, JPG/PNG/WebP. Si no cargás una, se usa automáticamente el logo de Moldpack.</span><input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => setImage(event.target.files?.[0] || null)}/><button type="button" disabled={!image} onClick={uploadImage}><Upload size={15}/>Subir imagen</button></label></div></div>
          <label className="seo-noindex"><input type="checkbox" checked={!!data.noindex} onChange={(event) => setData({ ...data, noindex: event.target.checked })}/><span><strong>Ocultar esta página de buscadores</strong><small>Usalo sólo para páginas que no deben aparecer en Google.</small></span></label>
          <div className="editor-actions"><button className="save" type="button" onClick={save}><Save size={17}/>Guardar SEO de {selected.name}</button></div>
        </div></article>
      </div>
    </div>
  </ModuleFrame>;
}

function NewsletterModule({ newsletter = { subscribers: [], campaigns: [] } }) {
  const active = newsletter.subscribers.filter((item) => item.status === 'active');
  const [selected, setSelected] = useState(active.map((item) => item.id));
  const [form, setForm] = useState({ subject: '', preheader: '', body: '', action_label: '', action_url: '', image: null });
  const toggle = (id) => setSelected((current) => current.includes(id) ? current.filter((value) => value !== id) : [...current, id]);
  const send = (event) => {
    event.preventDefault();
    if (!confirm(`¿Enviar esta campaña a ${selected.length} contacto${selected.length === 1 ? '' : 's'}?`)) return;
    const payload = new FormData(); selected.forEach((id) => payload.append('recipient_ids[]', id)); Object.entries(form).forEach(([key, value]) => value && payload.append(key, value));
    router.post('/admin/newsletter/campaigns', payload, { forceFormData: true, preserveScroll: true });
  };
  return <ModuleFrame eyebrow="Comunicación comercial" title="Newsletter" description="Creá campañas de marca y elegí exactamente quién las recibe.">
    <div className="newsletter-admin-layout">
      <article className="content-panel elevated newsletter-recipients"><div className="panel-heading"><div><span>Audiencia</span><h2>Suscriptores</h2></div><div className="module-badge"><Users size={15}/>{active.length}</div></div><div className="newsletter-select-actions"><button type="button" onClick={() => setSelected(active.map((item) => item.id))}>Seleccionar todos</button><button type="button" onClick={() => setSelected([])}>Limpiar</button><strong>{selected.length} seleccionados</strong></div><div className="newsletter-contact-list">{newsletter.subscribers.map((subscriber) => <label key={subscriber.id} className={subscriber.status !== 'active' ? 'inactive' : ''}><input type="checkbox" disabled={subscriber.status !== 'active'} checked={selected.includes(subscriber.id)} onChange={() => toggle(subscriber.id)}/><span><strong>{subscriber.name || subscriber.email.split('@')[0]}</strong><small>{subscriber.email}</small></span><b>{subscriber.status === 'active' ? 'Activo' : 'Baja'}</b></label>)}{!newsletter.subscribers.length && <div className="newsletter-empty"><Mail size={25}/><strong>Todavía no hay suscriptores</strong><span>Se incorporarán desde el formulario del footer.</span></div>}</div></article>
      <form className="content-panel elevated newsletter-composer" onSubmit={send}><div className="panel-heading"><div><span>Nueva campaña</span><h2>Diseñá el correo</h2></div><div className="module-badge"><Mail size={15}/>HTML de marca</div></div><div className="panel-body"><div className="form-grid"><label className="wide">Asunto<input required maxLength="180" value={form.subject} placeholder="Una novedad clara que invite a abrir" onChange={(event) => setForm({ ...form, subject: event.target.value })}/></label><label className="wide">Texto de vista previa<input maxLength="240" value={form.preheader} placeholder="Complementa el asunto en la bandeja de entrada" onChange={(event) => setForm({ ...form, preheader: event.target.value })}/></label><label className="wide">Contenido<RichEditor value={form.body} onChange={(body) => setForm((current) => ({ ...current, body }))}/></label><label>Texto del botón<input value={form.action_label} placeholder="Ver productos" onChange={(event) => setForm({ ...form, action_label: event.target.value })}/></label><label>Enlace del botón<input type="url" value={form.action_url} placeholder="https://moldpack.com.ar/productos" onChange={(event) => setForm({ ...form, action_url: event.target.value })}/></label><label className="wide newsletter-image">Imagen principal<input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => setForm({ ...form, image: event.target.files?.[0] || null })}/><small>Opcional. Recomendado: 1200×630 px y menos de 2 MB.</small></label></div><div className="newsletter-mail-preview"><div><img src="/assets/figma/exact/logo-header.png" alt="Moldpack"/></div>{form.image && <img src={URL.createObjectURL(form.image)} alt="Vista previa"/>}<section><h3>{form.subject || 'Título de la campaña'}</h3><div dangerouslySetInnerHTML={{ __html: form.body || '<p>El contenido profesional del newsletter se verá aquí.</p>' }}/>{form.action_label && <span>{form.action_label}</span>}</section></div><div className="editor-actions"><button className="save" type="submit" disabled={!selected.length || !form.subject || !form.body}><Mail size={17}/>Enviar a {selected.length} contactos</button></div></div></form>
    </div>
    <article className="content-panel elevated newsletter-history"><div className="panel-heading"><div><span>Historial</span><h2>Campañas enviadas</h2></div></div><div>{newsletter.campaigns.map((campaign) => <div className="campaign-row" key={campaign.id}><span><strong>{campaign.subject}</strong><small>{new Date(campaign.created_at).toLocaleString('es-AR')}</small></span><b>{campaign.recipient_count} destinatarios</b><em className={campaign.status}>{campaign.status}</em></div>)}{!newsletter.campaigns.length && <div className="newsletter-empty">Todavía no se enviaron campañas.</div>}</div></article>
  </ModuleFrame>;
}

function SocialModule({ initial = {} }) {
  const [links, setLinks] = useState(initial.links || []);
  const [savingId, setSavingId] = useState(null);
  const [deletingId, setDeletingId] = useState(null);
  useEffect(() => setLinks(initial.links || []), [initial]);
  const update = (index, key, value) => setLinks((current) => current.map((item, position) => position === index ? { ...item, [key]: value } : item));
  const add = () => setLinks((current) => [...current, { id: '', name: '', url: '', icon: '', file: null }]);
  const save = (item, index) => { const key = item.id || `new-${index}`; const form = new FormData(); ['id', 'name', 'url'].forEach((field) => item[field] && form.append(field, item[field])); if (item.file) form.append('icon', item.file); router.post('/admin/social-links', form, { forceFormData: true, preserveScroll: true, onStart: () => setSavingId(key), onFinish: () => setSavingId(null) }); };
  const remove = (item, index) => {
    if (!item.id) {
      setLinks((current) => current.filter((_, position) => position !== index));
      return;
    }
    if (!confirm(`¿Quitar ${item.name || 'esta red'} del footer?`)) return;
    router.delete('/admin/social-links/' + item.id, { preserveScroll: true, onStart: () => setDeletingId(item.id), onSuccess: () => setLinks((current) => current.filter((link) => link.id !== item.id)), onFinish: () => setDeletingId(null) });
  };
  return <ModuleFrame eyebrow="Pie del sitio" title="Redes sociales del footer" description="Cada red publicada necesita un enlace válido y su icono identificador." action={<button className="primary" type="button" onClick={add}><Plus size={17}/>Agregar red</button>}><article className="content-panel elevated social-admin"><div className="panel-heading"><div><span>Enlaces públicos</span><h2>Iconos del footer</h2></div></div><div className="social-link-list">{links.map((item, index) => { const key = item.id || `new-${index}`; const saving = savingId === key; const deleting = deletingId === item.id; return <div className="social-link-row" key={key}><div className="social-icon-preview">{item.file ? <img src={URL.createObjectURL(item.file)} alt=""/> : item.icon ? <img src={'/' + item.icon} alt=""/> : <Share2 size={20}/>}</div><label>Red social<input value={item.name || ''} placeholder="Instagram" onChange={(event) => update(index, 'name', event.target.value)}/></label><label className="social-url">Enlace completo<input type="url" value={item.url || ''} placeholder="https://instagram.com/moldpack" onChange={(event) => update(index, 'url', event.target.value)}/></label><label className="social-file">Icono SVG, PNG o WebP<input type="file" accept="image/svg+xml,image/png,image/webp" onChange={(event) => update(index, 'file', event.target.files?.[0] || null)}/></label><button type="button" className="social-save" disabled={saving || deleting} onClick={() => save(item, index)}>{saving ? <><span className="button-loader"/>Guardando…</> : <><Save size={16}/>Guardar</>}</button><button type="button" className="icon danger" disabled={saving || deleting} onClick={() => remove(item, index)} aria-label={item.id ? `Eliminar ${item.name || 'red social'}` : 'Descartar red sin guardar'}>{deleting ? <span className="button-loader dark"/> : <Trash2 size={16}/>}</button></div>; })}{!links.length && <div className="social-empty"><Share2 size={24}/><strong>No hay redes publicadas</strong><span>Agregá una red social para mostrarla en el footer.</span></div>}</div></article></ModuleFrame>;
}

function ModuleFrame({ eyebrow, title, description, action, children }) {
  return <div className="module-content"><div className="module-intro premium"><div><span>{eyebrow}</span><h1>{title}</h1><p>{description}</p></div>{action}</div>{children}</div>;
}

function StoreMapPreview({ location }) {
  const element = useRef(null);
  const latitude = Number(location?.latitude);
  const longitude = Number(location?.longitude);
  const valid = Number.isFinite(latitude) && Number.isFinite(longitude);
  useEffect(() => {
    if (!element.current || !valid) return undefined;
    const map = L.map(element.current, { zoomControl: false, attributionControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false }).setView([latitude, longitude], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    L.marker([latitude, longitude], { icon: L.icon({ iconUrl: '/assets/figma/exact/stores/pin.svg', iconSize: [37, 48], iconAnchor: [19, 43] }) }).addTo(map);
    return () => map.remove();
  }, [latitude, longitude, valid]);
  return valid ? <div className="stores-map-live" ref={element} aria-label="Vista previa del mapa"/> : <div className="stores-map-empty"><MapPin size={25}/><span>Ubicá la dirección para previsualizar el mapa.</span></div>;
}

function StoresModule({ initial = {} }) {
  const blank = { name: '', address: '', phone: '', email: '', latitude: '', longitude: '' };
  const [data, setData] = useState({ country: 'Argentina', load_more_label: 'Cargar más resultados', locations: [], ...(initial || {}), locations: initial?.locations || [] });
  const [selected, setSelected] = useState(0);
  const [locating, setLocating] = useState(false);
  const [locationMessage, setLocationMessage] = useState('');
  const location = data.locations[selected] || null;
  const change = (key, value) => setData((current) => ({ ...current, locations: current.locations.map((item, index) => index === selected ? { ...item, [key]: value } : item) }));
  const changeAddress = (value) => {
    setData((current) => ({ ...current, locations: current.locations.map((item, index) => index === selected ? { ...item, address: value, latitude: '', longitude: '' } : item) }));
    setLocationMessage('Dirección modificada. Volvé a ubicar el pin.');
  };
  const add = () => { setData((current) => ({ ...current, locations: [...current.locations, blank] })); setSelected(data.locations.length); setLocationMessage(''); };
  const remove = () => {
    if (!location || !confirm('¿Eliminar este punto de venta?')) return;
    setData((current) => ({ ...current, locations: current.locations.filter((_, index) => index !== selected) }));
    setSelected(Math.max(0, selected - 1));
    setLocationMessage('');
  };
  const locate = async () => {
    if (!location?.address) { setLocationMessage('Primero escribí una dirección completa.'); return; }
    setLocating(true); setLocationMessage('Buscando la dirección…');
    try {
      const response = await fetch(`/admin/stores/geocode?address=${encodeURIComponent(location.address)}`, { headers: { Accept: 'application/json' } });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || 'No fue posible ubicar la dirección.');
      setData((current) => ({ ...current, locations: current.locations.map((item, index) => index === selected ? { ...item, latitude: result.latitude, longitude: result.longitude } : item) }));
      setLocationMessage('Pin ubicado correctamente. Guardá los cambios para publicarlo.');
    } catch (error) { setLocationMessage(error.message); }
    finally { setLocating(false); }
  };
  return <ModuleFrame eyebrow="Puntos de venta" title="Dónde comprar" description="Administrá distribuidores y ubicaciones. El mapa coloca automáticamente el pin de Moldpack desde cada dirección." action={<button className="primary" type="button" onClick={add}><Plus size={18}/>Nuevo punto</button>}>
    <div className="stores-admin-layout">
      <article className="content-panel elevated stores-admin-index">
        <div className="panel-heading"><div><span>Directorio</span><h2>Puntos publicados</h2></div><div className="module-badge"><MapPin size={15}/>{data.locations.length}</div></div>
        <div className="stores-admin-global"><label>País visible<input value={data.country || ''} onChange={(event) => setData({ ...data, country: event.target.value })}/></label><label>Texto al pie<input value={data.load_more_label || ''} onChange={(event) => setData({ ...data, load_more_label: event.target.value })}/></label></div>
        <div className="stores-admin-list">{data.locations.map((item, index) => <button type="button" key={index} className={selected === index ? 'active' : ''} onClick={() => { setSelected(index); setLocationMessage(''); }}><span className="stores-admin-number">{String(index + 1).padStart(2, '0')}</span><span><strong>{item.name || 'Punto sin nombre'}</strong><small>{item.address || 'Dirección pendiente'}</small></span><ChevronRight size={16}/></button>)}</div>
        {!data.locations.length && <div className="stores-admin-empty"><MapPin size={24}/><span>Agregá el primer punto de venta.</span></div>}
      </article>
      <article className="content-panel elevated stores-admin-editor">
        {location ? <><div className="panel-heading"><div><span>Ubicación {selected + 1}</span><h2>Datos del distribuidor</h2></div><button className="icon danger" type="button" onClick={remove} aria-label="Eliminar punto"><Trash2 size={17}/></button></div>
          <div className="panel-body"><div className="form-grid">
            <label className="wide">Nombre o razón social<input value={location.name || ''} onChange={(event) => change('name', event.target.value)}/></label>
            <label className="wide">Dirección completa<input value={location.address || ''} placeholder="Calle, número, ciudad, provincia, Argentina" onChange={(event) => changeAddress(event.target.value)}/><small>Incluí ciudad y provincia para que el pin se ubique con precisión.</small></label>
            <label>Teléfonos<input value={location.phone || ''} placeholder="Separalos con /" onChange={(event) => change('phone', event.target.value)}/></label>
            <label>Email<input type="email" value={location.email || ''} onChange={(event) => change('email', event.target.value)}/></label>
          </div>
          <div className="stores-geocode"><div><MapPin size={19}/><span><strong>Posición del pin</strong><small>{locationMessage || 'Se calcula desde la dirección; también podés ajustar las coordenadas.'}</small></span></div><button type="button" disabled={locating} onClick={locate}><Search size={15}/>{locating ? 'Ubicando…' : 'Ubicar automáticamente'}</button></div>
          <div className="form-grid stores-coordinates"><label>Latitud<input type="number" step="any" value={location.latitude ?? ''} onChange={(event) => change('latitude', event.target.value)}/></label><label>Longitud<input type="number" step="any" value={location.longitude ?? ''} onChange={(event) => change('longitude', event.target.value)}/></label></div>
          <div className="stores-map-preview"><StoreMapPreview location={location}/></div>
          <div className="editor-actions"><button className="save" type="button" onClick={() => router.put('/admin/settings/stores', { value: data }, { preserveScroll: true })}><Save size={17}/>Guardar y publicar</button></div></div></> : <div className="stores-admin-placeholder"><MapPin size={32}/><h2>Seleccioná una ubicación</h2><p>Elegí un punto del directorio o creá uno nuevo.</p><button className="primary" type="button" onClick={add}><Plus size={17}/>Nuevo punto</button></div>}
      </article>
    </div>
  </ModuleFrame>;
}

function SettingsModule({ type, initial = {} }) {
  const configs = {
    quality: { eyebrow: 'Confianza de marca', title: 'Calidad', description: 'Presentá procesos, certificaciones y argumentos de calidad.', fields: [['title', 'Título'], ['body', 'Descripción', 'textarea'], ['button_label', 'Texto del botón'], ['button_url', 'Destino del botón']] },
    stores: { eyebrow: 'Puntos de venta', title: 'Dónde comprar', description: 'Gestioná el mensaje comercial y la ubicación de distribuidores.', fields: [['title', 'Título'], ['body', 'Descripción', 'textarea'], ['map_url', 'Enlace del mapa']] },
    contact: { eyebrow: 'Datos públicos', title: 'Contacto', description: 'Información que se muestra en el header, footer y accesos rápidos.', fields: [['address', 'Dirección'], ['phone', 'Teléfono'], ['email', 'Correo electrónico'], ['whatsapp', 'WhatsApp con código de país']] },
    newsletter: { eyebrow: 'Comunidad', title: 'Newsletter', description: 'Configurá el formulario de suscripción y su mensaje de confirmación.', fields: [['title', 'Título'], ['placeholder', 'Texto del campo'], ['success_message', 'Mensaje de confirmación', 'textarea']] },
    social: { eyebrow: 'Presencia digital', title: 'Redes sociales', description: 'Conectá las cuentas oficiales que aparecen en el footer.', fields: [['instagram', 'Instagram'], ['facebook', 'Facebook'], ['youtube', 'YouTube']] },
    client_portal: { eyebrow: 'Zona privada', title: 'Configuración del portal', description: 'Controlá la información comercial y bancaria visible para clientes.', fields: [['important_title', 'Título de información importante'], ['important_body', 'Condiciones y aclaraciones', 'textarea'], ['pickup_address', 'Dirección para retiro'], ['bank_holder', 'Titular de la cuenta'], ['bank_name', 'Banco'], ['bank_cbu', 'CBU'], ['bank_alias', 'Alias'], ['bank_tax_id', 'CUIT']] },
  };
  const config = configs[type];
  const [data, setData] = useState(initial || {});
  return <ModuleFrame eyebrow={config.eyebrow} title={config.title} description={config.description}><article className="content-panel elevated"><div className="panel-heading"><div><span>Módulo</span><h2>{config.title}</h2></div><div className="module-badge"><Sparkles size={15} />Activo</div></div><div className="panel-body"><div className="form-grid">{config.fields.map(([key, label, control]) => <label className={control === 'textarea' ? 'wide' : ''} key={key}>{label}{control === 'textarea' ? <textarea value={data[key] || ''} onChange={(event) => setData({ ...data, [key]: event.target.value })} /> : <input value={data[key] || ''} onChange={(event) => setData({ ...data, [key]: event.target.value })} />}</label>)}</div><div className="editor-actions"><button className="save" type="button" onClick={() => router.put('/admin/settings/' + type, { value: data })}><Save size={17} />Guardar cambios</button></div></div></article></ModuleFrame>;
}

function ContactModule({ initial = {}, inquiries = { data: [] } }) {
  const [data, setData] = useState({ intro: '', address: '', city: '', phone: '', email: '', ...(initial || {}) });
  const records = inquiries?.data || [];
  const unread = records.filter((item) => !item.read_at).length;
  const save = () => router.put('/admin/settings/contact', { value: { intro: data.intro, address: data.address, city: data.city, phone: data.phone, email: data.email } }, { preserveScroll: true });
  const page = (url) => url && router.get(url, {}, { preserveState: true, preserveScroll: true });

  return <ModuleFrame eyebrow="Atención comercial" title="Contacto" description="Editá los datos públicos, la ubicación y revisá todas las consultas recibidas.">
    <div className="contact-admin-grid">
      <article className="content-panel elevated">
        <div className="panel-heading"><div><span>Datos públicos</span><h2>Información de contacto</h2></div><div className="module-badge"><MapPin size={15} />Visible</div></div>
        <div className="panel-body">
          <div className="contact-admin-note"><ShieldCheck size={19}/><span><strong>Sólo contenido público</strong><small>El mapa, el pin y su configuración técnica están protegidos para evitar que la ubicación oficial se rompa.</small></span></div><div className="form-grid">
            <label className="wide">Texto introductorio<textarea value={data.intro || ''} onChange={(event) => setData({ ...data, intro: event.target.value })}/></label>
            <label className="wide">Dirección<input value={data.address || ''} onChange={(event) => setData({ ...data, address: event.target.value })} /></label>
            <label className="wide">Ciudad y provincia<input value={data.city || ''} onChange={(event) => setData({ ...data, city: event.target.value })} /></label>
            <label>Teléfonos<input value={data.phone || ''} placeholder="4727-2836/2837" onChange={(event) => setData({ ...data, phone: event.target.value })} /></label>
            <label>Correo electrónico<input type="email" value={data.email || ''} onChange={(event) => setData({ ...data, email: event.target.value })} /></label>
          </div>
          <div className="contact-official-map"><MapPin size={20}/><span><strong>Ubicación oficial de Moldpack</strong><small>Dante Alighieri 1377 · El botón “Cómo llegar” abre la ficha correcta de Moldpack.</small></span><a href="https://maps.app.goo.gl/gVUD5k7wC3zZhwbX" target="_blank" rel="noreferrer">Ver en Maps</a></div>
          <div className="editor-actions"><button className="save" type="button" onClick={save}><Save size={17} />Guardar datos públicos</button></div>
        </div>
      </article>

      <article className="content-panel elevated contact-inbox">
        <div className="panel-heading"><div><span>Bandeja de entrada</span><h2>Consultas recibidas</h2></div><div className={`module-badge ${unread ? 'has-unread' : ''}`}><Mail size={15} />{unread ? `${unread} nuevas` : `${inquiries.total || 0} total`}</div></div>
        <div className="inquiry-list">
          {records.map((inquiry) => <article className={`inquiry-card ${inquiry.read_at ? 'read' : 'unread'}`} key={inquiry.id}>
            <header><div><strong>{inquiry.name}</strong><span>{inquiry.company || 'Sin empresa'}</span></div><time>{new Date(inquiry.created_at).toLocaleString('es-AR', { dateStyle: 'medium', timeStyle: 'short' })}</time></header>
            <p>{inquiry.message}</p>
            <footer><a href={`mailto:${inquiry.email}`}>{inquiry.email}</a><a href={`tel:${inquiry.phone.replace(/[^+\d]/g, '')}`}>{inquiry.phone}</a>{!inquiry.read_at && <button type="button" onClick={() => router.put(`/admin/consultas/${inquiry.id}/read`, {}, { preserveScroll: true })}><Check size={14} />Marcar leída</button>}</footer>
          </article>)}
          {!records.length && <div className="inquiry-empty"><Mail size={25} /><strong>Todavía no hay consultas</strong><span>Los mensajes enviados desde la web aparecerán aquí.</span></div>}
        </div>
        {inquiries.last_page > 1 && <div className="inquiry-pagination"><button type="button" disabled={!inquiries.prev_page_url} onClick={() => page(inquiries.prev_page_url)}>Anterior</button><span>Página {inquiries.current_page} de {inquiries.last_page}</span><button type="button" disabled={!inquiries.next_page_url} onClick={() => page(inquiries.next_page_url)}>Siguiente</button></div>}
      </article>
    </div>
  </ModuleFrame>;
}

function WhatsAppModule({ contact = {} }) {
  const [number, setNumber] = useState(contact.whatsapp || '');
  useEffect(() => setNumber(contact.whatsapp || ''), [contact.whatsapp]);
  const digits = number.replace(/\D+/g, '');
  const valid = digits.length >= 8 && digits.length <= 15;
  const save = () => router.put('/admin/settings/whatsapp', { value: { number } }, { preserveScroll: true });
  return <ModuleFrame eyebrow="Atención inmediata" title="WhatsApp" description="Configurá el número del botón flotante de WhatsApp que aparece en todo el sitio público.">
    <article className="content-panel elevated">
      <div className="panel-heading"><div><span>Botón flotante</span><h2>Número de WhatsApp</h2></div><div className="module-badge"><WhatsAppIcon size={15} />{contact.whatsapp ? 'Visible' : 'Sin número'}</div></div>
      <div className="panel-body">
        <div className="contact-admin-note"><WhatsAppIcon size={19} /><span><strong>Formato internacional</strong><small>Código de país + código de área + número, sin 0 ni 15. Ejemplo para Argentina: 54 9 11 4727 2836.</small></span></div>
        <div className="form-grid"><label className="wide">Número de WhatsApp<input inputMode="tel" value={number} placeholder="54 9 11 4727 2836" onChange={(event) => setNumber(event.target.value)} /><small>{digits ? `Se publicará como wa.me/${digits}` : 'Ingresá el número completo con código de país.'}</small></label></div>
        {valid && <div className="contact-official-map"><WhatsAppIcon size={20} /><span><strong>Vista previa del botón</strong><small>Abre un chat con +{digits}</small></span><a href={`https://wa.me/${digits}`} target="_blank" rel="noreferrer">Probar</a></div>}
        <div className="editor-actions"><button className="save" type="button" disabled={!valid} onClick={save}><Save size={17} />Guardar número</button></div>
      </div>
    </article>
  </ModuleFrame>;
}

function UsersModule({ users }) {
  const blank = { name: '', email: '', password: '', password_confirmation: '', is_admin: true };
  const [form, setForm] = useState(blank);
  return <ModuleFrame eyebrow="Seguridad y acceso" title="Usuarios" description="Administrá quién puede ingresar y modificar el sitio." action={<button className="primary" type="button" onClick={() => document.querySelector('#new-user-name')?.focus()}><Plus size={18} />Nuevo usuario</button>}><div className="users-layout"><article className="content-panel elevated"><div className="panel-heading"><div><span>Equipo</span><h2>Usuarios autorizados</h2></div><div className="module-badge"><Users size={15} />{users.length}</div></div><div className="user-list">{users.map((user) => <div className="user-row" key={user.id}><div className="avatar">{user.name.slice(0, 2).toUpperCase()}</div><div><strong>{user.name}</strong><span>{user.email}</span></div><span className="role">{user.is_admin ? 'Administrador' : 'Editor'}</span><button type="button" className="icon danger" onClick={() => confirm('¿Eliminar este usuario?') && router.delete('/admin/users/' + user.id)}><Trash2 size={17} /></button></div>)}</div></article><article className="content-panel elevated new-user"><div className="panel-heading"><div><span>Nueva cuenta</span><h2>Invitar usuario</h2></div></div><div className="panel-body"><div className="form-grid single"><label>Nombre<input id="new-user-name" value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} /></label><label>Correo electrónico<input type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} /></label><label>Contraseña temporal<input type="password" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} /></label><label>Confirmar contraseña<input type="password" value={form.password_confirmation} onChange={(event) => setForm({ ...form, password_confirmation: event.target.value })} /></label></div><div className="editor-actions"><button className="save" type="button" onClick={() => router.post('/admin/users', form)}><Plus size={17} />Crear usuario</button></div></div></article></div></ModuleFrame>;
}

function AppleNotification({ flash, errors = {} }) {
  const errorMessage = Object.values(errors || {}).flat().filter(Boolean)[0];
  const incoming = flash?.success ? { type: 'success', title: 'Cambios guardados', message: flash.success } : flash?.error ? { type: 'error', title: 'No se pudo completar', message: flash.error } : errorMessage ? { type: 'error', title: 'Revisá la información', message: String(errorMessage) } : null;
  const [notice, setNotice] = useState(incoming);
  useEffect(() => { if (incoming) setNotice({ ...incoming, id: Date.now() }); }, [flash?.success, flash?.error, errorMessage]);
  useEffect(() => router.on('success', (event) => { const props = event.detail.page.props || {}; const validation = Object.values(props.errors || {}).flat().filter(Boolean)[0]; const next = props.flash?.success ? { type: 'success', title: 'Cambios guardados', message: props.flash.success } : props.flash?.error ? { type: 'error', title: 'No se pudo completar', message: props.flash.error } : validation ? { type: 'error', title: 'Revisá la información', message: String(validation) } : null; if (next) setNotice({ ...next, id: Date.now() }); }), []);
  useEffect(() => { if (!notice) return undefined; const timer = window.setTimeout(() => setNotice(null), notice.type === 'error' ? 6200 : 4200); return () => window.clearTimeout(timer); }, [notice?.id]);
  return <div className="apple-toast-viewport" aria-live="polite" aria-atomic="true">{notice && <div key={notice.id || notice.message} className={`apple-toast ${notice.type}`} role={notice.type === 'error' ? 'alert' : 'status'}><div className="apple-toast-symbol" aria-hidden="true">{notice.type === 'error' ? <CircleAlert size={19} strokeWidth={2.2} /> : <Check size={19} strokeWidth={2.7} />}</div><div className="apple-toast-copy"><strong>{notice.title}</strong><span>{notice.message}</span></div><button type="button" onClick={() => setNotice(null)} aria-label="Cerrar notificación"><X size={16} /></button><i className="apple-toast-timer" aria-hidden="true" /></div>}</div>;
}

const money = (value) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(Number(value || 0));
const date = (value) => value ? new Intl.DateTimeFormat('es-AR').format(new Date(value)) : '—';
const statusName = { pending:'Pendiente',approved:'Aprobado',preparing:'En preparación',ready:'Listo',dispatched:'Despachado',delivered:'Entregado',cancelled:'Cancelado',invoiced:'Facturado',credited:'Nota de crédito',issued:'Emitida',draft:'Borrador' };

function PrivateHeader({ eyebrow, title, text, count }) { return <div className="module-intro premium private-intro"><div><span>{eyebrow}</span><h1>{title}</h1><p>{text}</p></div>{count !== undefined && <div className="private-count"><strong>{count}</strong><span>registros</span></div>}</div>; }
const editableClient = (client) => ({
  username: client.username || '', name: client.name || '', first_name: client.first_name || '', last_name: client.last_name || '',
  business_name: client.business_name || '', tax_id: client.tax_id || '', document_id: client.document_id || '', email: client.email || '',
  alternate_email: client.alternate_email || '', phone: client.phone || '', billing_address: client.billing_address || '',
  delivery_address: client.delivery_address || '', started_on: client.started_on || '', discount_percent: Number(client.discount_percent || 0),
  show_prices: Boolean(client.show_prices), is_active: Boolean(client.is_active),
});

function ClientModule({ clients=[], total=clients.length }) {
  const [query,setQuery]=useState('');
  const [records,setRecords]=useState(clients);
  const [pagination,setPagination]=useState({current_page:1,last_page:Math.max(1,Math.ceil(total/100)),total});
  const [loading,setLoading]=useState(false);
  const [selected,setSelected]=useState(null);
  const [form,setForm]=useState(null);
  const [access,setAccess]=useState({ admin_password:'', password:'', password_confirmation:'' });
  const [revealed,setRevealed]=useState('');
  const [accessError,setAccessError]=useState('');
  const [busy,setBusy]=useState(false);
  const load=async(page=1,search=query)=>{setLoading(true);try{const response=await fetch(`/admin/zona-privada/datos/clientes?per_page=100&page=${page}&q=${encodeURIComponent(search)}`,{headers:{Accept:'application/json'}});if(!response.ok)throw new Error();const data=await response.json();setRecords(data.data||[]);setPagination({current_page:data.current_page,last_page:data.last_page,total:data.total});}finally{setLoading(false);}};
  useEffect(()=>{const timer=setTimeout(()=>load(1,query),query?280:0);return()=>clearTimeout(timer);},[query]);
  useEffect(()=>{if(!query)setRecords(clients);},[clients]);
  const list=records;
  const open=(client)=>{setSelected(client);setForm(editableClient(client));setAccess({admin_password:'',password:'',password_confirmation:''});setRevealed('');setAccessError('');};
  const close=()=>{setSelected(null);setForm(null);setRevealed('');setAccessError('');};
  const field=(key,value)=>setForm(current=>({...current,[key]:value}));
  const toggleStatus=(client)=>router.put('/admin/clientes/'+client.id,{...editableClient(client),is_active:!client.is_active},{preserveScroll:true});
  const generatePassword=()=>{const chars='ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';const values=crypto.getRandomValues(new Uint32Array(14));const generated=Array.from(values,n=>chars[n%chars.length]).join('');setAccess(current=>({...current,password:generated,password_confirmation:generated}));setRevealed(generated);};
  const viewPassword=async()=>{setBusy(true);setAccessError('');setRevealed('');try{const response=await fetch(`/admin/clientes/${selected.id}/password/view`,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({admin_password:access.admin_password})});const data=await response.json();if(!response.ok)throw new Error(data.message||Object.values(data.errors||{}).flat()[0]||'No se pudo consultar la clave.');setRevealed(data.password);}catch(error){setAccessError(error.message);}finally{setBusy(false);}};
  const savePassword=()=>{setAccessError('');router.post(`/admin/clientes/${selected.id}/password`,access,{preserveScroll:true,onSuccess:()=>setAccess({admin_password:'',password:'',password_confirmation:''})});};
  return <div className="module-content private-module">
    <PrivateHeader eyebrow="ZONA PRIVADA" title="Listado de clientes" text="Altas, aprobación, datos fiscales, acceso y condiciones comerciales." count={pagination.total}/>
    <label className="private-search"><Search size={18}/><input value={query} onChange={e=>setQuery(e.target.value)} placeholder="Buscar cliente, empresa, CUIT, DNI o email…"/></label>
    <div className={'private-table-wrap '+(loading?'is-loading':'')}><table className="private-table"><thead><tr><th>Cliente</th><th>Empresa / CUIT</th><th>Contacto</th><th>Pedidos</th><th>Descuento</th><th>Estado</th><th aria-label="Acciones" /></tr></thead><tbody>{list.map(c=><tr key={c.id}><td><strong>{c.name}</strong><small>@{c.username}</small></td><td>{c.business_name||'—'}<small>{c.tax_id||'Sin CUIT'}</small></td><td>{c.email}<small>{c.phone||'Sin teléfono'}</small></td><td>{c.orders_count}</td><td>{c.discount_percent}%</td><td><button type="button" className={'private-state '+(c.is_active?'ok':'wait')} onClick={()=>toggleStatus(c)}>{c.is_active?'Activo':'Pendiente'}</button></td><td><button className="client-edit-trigger" type="button" onClick={()=>open(c)} aria-label={`Editar ${c.name}`}><Pencil size={16}/><span>Editar</span></button></td></tr>)}</tbody></table>{!list.length&&!loading&&<div className="client-list-empty">No encontramos clientes con esa búsqueda.</div>}</div>
    {pagination.last_page>1&&<div className="client-pagination"><button type="button" disabled={pagination.current_page<=1||loading} onClick={()=>load(pagination.current_page-1)}>Anterior</button><span>Página <strong>{pagination.current_page}</strong> de {pagination.last_page}</span><button type="button" disabled={pagination.current_page>=pagination.last_page||loading} onClick={()=>load(pagination.current_page+1)}>Siguiente</button></div>}
    {selected&&form&&<div className="client-modal-backdrop client-modal-enter" onMouseDown={e=>e.target===e.currentTarget&&close()}><section className="client-editor client-dialog-enter" role="dialog" aria-modal="true" aria-labelledby="client-editor-title">
      <header className="client-editor-head"><div><span>Cliente #{selected.id}</span><h2 id="client-editor-title">{selected.business_name||selected.name}</h2><p>Editá el perfil completo y administrá su acceso desde un solo lugar.</p></div><button type="button" onClick={close} aria-label="Cerrar editor"><X size={19}/></button></header>
      <div className="client-editor-body"><form className="client-profile-form" onSubmit={e=>{e.preventDefault();router.put('/admin/clientes/'+selected.id,form,{preserveScroll:true,onSuccess:close})}}>
        <fieldset><legend>Identidad y empresa</legend><div className="client-form-grid"><label>Usuario<input required value={form.username} onChange={e=>field('username',e.target.value)}/></label><label>Nombre visible<input required value={form.name} onChange={e=>field('name',e.target.value)}/></label><label>Nombre<input value={form.first_name} onChange={e=>field('first_name',e.target.value)}/></label><label>Apellido<input value={form.last_name} onChange={e=>field('last_name',e.target.value)}/></label><label className="wide">Empresa / razón social<input value={form.business_name} onChange={e=>field('business_name',e.target.value)}/></label><label>DNI<input inputMode="numeric" value={form.document_id} onChange={e=>field('document_id',e.target.value)}/></label><label>CUIT<input inputMode="numeric" value={form.tax_id} onChange={e=>field('tax_id',e.target.value)}/></label></div></fieldset>
        <fieldset><legend>Contacto y direcciones</legend><div className="client-form-grid"><label>Email principal<input required type="email" value={form.email} onChange={e=>field('email',e.target.value)}/></label><label>Email alternativo<input type="email" value={form.alternate_email} onChange={e=>field('alternate_email',e.target.value)}/></label><label>Teléfono<input value={form.phone} onChange={e=>field('phone',e.target.value)}/></label><label>Fecha de alta<input value={form.started_on} onChange={e=>field('started_on',e.target.value)}/></label><label className="wide">Dirección comercial<textarea rows="2" value={form.billing_address} onChange={e=>field('billing_address',e.target.value)}/></label><label className="wide">Dirección de entrega<textarea rows="2" value={form.delivery_address} onChange={e=>field('delivery_address',e.target.value)}/></label></div></fieldset>
        <fieldset><legend>Condiciones comerciales</legend><div className="client-form-grid compact"><label>Descuento general (%)<input type="number" min="0" max="100" step=".01" value={form.discount_percent} onChange={e=>field('discount_percent',e.target.value)}/></label><label className="client-switch"><input type="checkbox" checked={form.show_prices} onChange={e=>field('show_prices',e.target.checked)}/><span><strong>Mostrar precios</strong><small>Habilita valores en la zona privada.</small></span></label><label className="client-switch"><input type="checkbox" checked={form.is_active} onChange={e=>field('is_active',e.target.checked)}/><span><strong>Cuenta activa</strong><small>Permite al cliente iniciar sesión.</small></span></label></div></fieldset>
        <div className="client-editor-actions"><button type="button" onClick={close}>Cancelar</button><button className="primary" type="submit"><Save size={16}/>Guardar perfil</button></div>
      </form>
      <aside className="client-access-panel"><div className="client-access-title"><KeyRound size={20}/><div><strong>Acceso del cliente</strong><span>Protegido con tu contraseña de administrador.</span></div></div><label>Tu contraseña de administrador<input type="password" autoComplete="current-password" value={access.admin_password} onChange={e=>setAccess({...access,admin_password:e.target.value})}/></label><button type="button" className="access-secondary" disabled={busy||!access.admin_password} onClick={viewPassword}><Eye size={16}/>{busy?'Consultando…':'Ver clave recuperable'}</button><div className="access-divider"><span>o establecer una nueva</span></div><label>Nueva contraseña<input type="text" autoComplete="new-password" value={access.password} onChange={e=>setAccess({...access,password:e.target.value})}/></label><label>Confirmar contraseña<input type="text" autoComplete="new-password" value={access.password_confirmation} onChange={e=>setAccess({...access,password_confirmation:e.target.value})}/></label><button type="button" className="access-secondary" onClick={generatePassword}><Sparkles size={16}/>Generar clave segura</button>{revealed&&<div className="revealed-password"><span>Clave para informar al cliente</span><strong>{revealed}</strong><button type="button" onClick={()=>navigator.clipboard?.writeText(revealed)}>Copiar</button></div>}{accessError&&<p className="client-access-error"><CircleAlert size={15}/>{accessError}</p>}<button type="button" className="access-save" disabled={!access.admin_password||!access.password||access.password!==access.password_confirmation} onClick={savePassword}><KeyRound size={16}/>Guardar nueva contraseña</button><p className="access-note">Las claves hasheadas del sistema anterior no se pueden revelar. Al crear una nueva, queda disponible aquí de forma cifrada y el login usa un hash seguro.</p></aside>
      </div>
    </section></div>}
  </div>;
}
function OrderModule({ orders=[], mode='all' }) { const [query,setQuery]=useState(''); const filtered=orders.filter(o=>{if(mode==='logistics'&&['delivered','cancelled'].includes(o.status))return false;if(mode==='accounting'&&o.billing_status!=='pending')return false;if(mode==='billed'&&o.billing_status!=='invoiced')return false;return [o.number,o.cliente?.name,o.cliente?.business_name].some(v=>(v||'').toLowerCase().includes(query.toLowerCase()))}); const title={cart:'Carrito y pedidos',logistics:'Pedidos de logística',accounting:'Pedidos a facturar',billed:'Pedidos facturados',all:'Todos los pedidos'}[mode]; return <div className="module-content private-module"><PrivateHeader eyebrow={mode==='logistics'?'LOGÍSTICA':mode==='accounting'||mode==='billed'?'CONTABILIDAD':'ZONA PRIVADA'} title={title} text="Seguimiento operativo con trazabilidad de estados, productos y totales." count={filtered.length}/><label className="private-search"><Search size={18}/><input value={query} onChange={e=>setQuery(e.target.value)} placeholder="Buscar pedido o cliente…"/></label><div className="order-board">{filtered.map(o=><article className="order-card" key={o.id}><header><div><span>{date(o.created_at)}</span><h3>{o.number}</h3><p>{o.cliente?.business_name||o.cliente?.name}</p></div><strong>{money(o.total)}</strong></header><div className="order-card-meta"><span>{o.items?.length||0} productos</span><span>Facturación: {statusName[o.billing_status]||o.billing_status}</span></div><details><summary>Detalle del pedido</summary>{o.items?.map(i=><div className="order-line" key={i.id}><span>{i.quantity} × {i.name}</span><b>{mode==='logistics'?`${i.prepared_quantity}/${i.quantity}`:money(i.line_total)}</b></div>)}</details><form onSubmit={e=>{e.preventDefault();const fd=new FormData(e.currentTarget);router.put('/admin/pedidos/'+o.id,{status:fd.get('status'),notes:fd.get('notes')},{preserveScroll:true})}}><select name="status" defaultValue={o.status}>{Object.entries(statusName).slice(0,7).map(([v,l])=><option value={v} key={v}>{l}</option>)}</select><input name="notes" defaultValue={o.notes||''} placeholder="Nota interna"/><button type="submit">Actualizar</button></form></article>)}</div>{!filtered.length&&<div className="empty-state"><h2>Sin resultados</h2><p>No hay pedidos en esta etapa.</p></div>}</div>; }
function StockModule({ products=[] }) { return <div className="module-content private-module"><PrivateHeader eyebrow="LOGÍSTICA" title="Stock" text="Lectura operativa del inventario publicado en el catálogo." count={products.length}/><div className="private-table-wrap"><table className="private-table"><thead><tr><th>Producto</th><th>SKU</th><th>Precio</th><th>Existencia</th><th>Nivel</th></tr></thead><tbody>{products.map(p=><tr key={p.id}><td><strong>{p.name}</strong></td><td>{p.sku||'—'}</td><td>{money(p.price)}</td><td><strong>{p.stock}</strong></td><td><span className={'stock-level '+(p.stock<=0?'danger':p.stock<10?'warning':'ok')}>{p.stock<=0?'Sin stock':p.stock<10?'Bajo':'Disponible'}</span></td></tr>)}</tbody></table></div></div>; }
function InvoiceModule({ invoices=[] }) { return <div className="module-content private-module"><PrivateHeader eyebrow="CONTABILIDAD" title="Facturas" text="Comprobantes vinculados a cada pedido, con estado y referencia externa." count={invoices.length}/><div className="private-table-wrap"><table className="private-table"><thead><tr><th>Comprobante</th><th>Cliente</th><th>Pedido</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead><tbody>{invoices.map(i=><tr key={i.id}><td><strong>{i.type} {i.number}</strong><small>{i.external_reference||'Sin referencia externa'}</small></td><td>{i.order?.cliente?.business_name||i.order?.cliente?.name}</td><td>{i.order?.number}</td><td>{date(i.issued_at)}</td><td>{money(i.total)}</td><td><span className="private-state ok">{statusName[i.status]||i.status}</span></td></tr>)}</tbody></table></div></div>; }
function PaymentModule({ payments=[] }) { return <div className="module-content private-module"><PrivateHeader eyebrow="CONTABILIDAD" title="Comprobantes de pago" text="Validá los pagos informados por los clientes y consultá sus comprobantes." count={payments.length}/><div className="private-table-wrap"><table className="private-table"><thead><tr><th>Cliente</th><th>Fecha</th><th>Banco / sucursal</th><th>Importe</th><th>Comprobante</th><th>Estado</th></tr></thead><tbody>{payments.map(p=><tr key={p.id}><td><strong>{p.cliente?.business_name||p.cliente?.name}</strong><small>{p.cliente?.email}</small></td><td>{date(p.paid_at)}</td><td>{p.bank}<small>{p.branch||'Sin sucursal'}</small></td><td><strong>{money(p.amount)}</strong></td><td>{p.receipt_path?<a href={'/storage/'+p.receipt_path} target="_blank" rel="noreferrer">Ver archivo</a>:'—'}</td><td><select value={p.status} onChange={e=>router.put('/admin/pagos/'+p.id,{status:e.target.value},{preserveScroll:true})}><option value="pending">Pendiente</option><option value="verified">Verificado</option><option value="rejected">Rechazado</option></select></td></tr>)}</tbody></table>{!payments.length&&<div className="client-list-empty">Todavía no se informaron pagos.</div>}</div></div>; }
function ExportModule({ commerce }) { const total=commerce.totals||{}; return <div className="module-content private-module"><PrivateHeader eyebrow="CONTABILIDAD" title="Exportación" text="Descargá la base completa en CSV compatible con Excel; se genera por lotes para grandes volúmenes."/><div className="export-grid"><a href="/admin/zona-privada/exportar/clientes"><Users size={24}/><strong>Clientes</strong><span>{total.clients||commerce.clients.length} registros · CSV completo</span></a><a href="/admin/zona-privada/exportar/pedidos"><Package size={24}/><strong>Pedidos</strong><span>{total.orders||commerce.orders.length} registros · CSV completo</span></a><a href="/admin/zona-privada/exportar/stock"><Grid2X2 size={24}/><strong>Stock</strong><span>{commerce.products.length} productos · CSV completo</span></a></div></div>; }

const intelligenceLabels={visitors:'Visitantes humanos',sessions:'Sesiones',pageviews:'Páginas vistas',requests:'Solicitudes',bots:'Bots probables',security:'Eventos de seguridad',errors:'Errores HTTP',avg_response_ms:'Respuesta media'};
const intelligenceIcons={visitors:Users,sessions:MousePointerClick,pageviews:BarChart3,requests:Activity,bots:Bot,security:ShieldAlert,errors:CircleAlert,avg_response_ms:Gauge};
function MetricBars({items=[],empty='Todavía no hay datos para este período.'}){const max=Math.max(1,...items.map(item=>item.value));return <div className="intel-bars">{items.map(item=><div className="intel-bar" key={item.label}><div><span title={item.label}>{item.label}</span><strong>{item.value.toLocaleString('es-AR')}</strong></div><i><b style={{width:`${Math.max(3,item.value/max*100)}%`}}/></i></div>)}{!items.length&&<p className="intel-empty">{empty}</p>}</div>}
function IntelligenceDashboard({data}){
  const metrics=data?.metrics||{};const timeline=data?.timeline||[];const max=Math.max(1,...timeline.map(item=>item.requests));
  return <div className="intelligence-dashboard">
    <header className="intel-header"><div><span>SECURITY &amp; TRAFFIC INTELLIGENCE CENTER</span><h1>Vista general</h1><p>Tráfico, conversiones y señales operativas obtenidas del sitio real.</p></div><div className="intel-header-actions"><div className="intel-export-group"><a className="intel-export" href="/admin/database/export/sqlite"><Upload size={15}/>Respaldo local completo</a><a className="intel-export secondary" href="/admin/database/export/mysql"><Upload size={15}/>Datos para MySQL</a></div><div className="intel-range" aria-label="Período">{['today','24h','7d','30d','90d'].map(range=><a key={range} className={data?.range===range?'active':''} href={`/dashboard?range=${range}`}>{range==='today'?'Hoy':range}</a>)}</div></div></header>
    {!data?.available&&<div className="intel-notice"><ShieldCheck size={20}/><div><strong>Instrumentación preparada</strong><span>Ejecutá las migraciones para comenzar a registrar actividad. No se muestran cifras simuladas.</span></div></div>}
    <section className="intel-kpis">{Object.keys(intelligenceLabels).map(key=>{const Icon=intelligenceIcons[key];return <article key={key}><span className={`intel-kpi-icon ${key}`}><Icon size={19}/></span><div><small>{intelligenceLabels[key]}</small><strong>{Number(metrics[key]||0).toLocaleString('es-AR')}{key==='avg_response_ms'?' ms':''}</strong></div></article>})}</section>
    <section className="intel-grid intel-grid-main"><article className="intel-panel intel-timeline"><header><div><span>Tráfico verificado</span><h2>Solicitudes por día</h2></div><small>Desde {new Date(data?.from||Date.now()).toLocaleDateString('es-AR')}</small></header><div className="intel-chart">{timeline.map(item=><div className="intel-chart-column" key={item.day} title={`${item.day}: ${item.requests}`}><i style={{height:`${Math.max(5,item.requests/max*100)}%`}}/><span>{new Date(`${item.day}T12:00:00`).toLocaleDateString('es-AR',{day:'2-digit',month:'short'})}</span></div>)}{!timeline.length&&<p className="intel-empty">Sin actividad capturada en este período.</p>}</div></article><article className="intel-panel"><header><div><span>Contenido</span><h2>Páginas principales</h2></div></header><MetricBars items={data?.top_pages}/></article></section>
    <section className="intel-grid intel-grid-three"><article className="intel-panel"><header><div><span>Adquisición</span><h2>Origen del tráfico</h2></div></header><MetricBars items={data?.sources}/></article><article className="intel-panel"><header><div><span>Tecnología</span><h2>Dispositivos</h2></div></header><MetricBars items={data?.devices}/></article><article className="intel-panel intel-conversions"><header><div><span>Negocio</span><h2>Conversiones reales</h2></div></header><div><p><span>Consultas</span><strong>{metrics.contacts||0}</strong></p><p><span>Altas newsletter</span><strong>{metrics.subscribers||0}</strong></p><p><span>Pedidos</span><strong>{metrics.orders||0}</strong></p></div></article></section>
    <section className="intel-grid intel-grid-main"><article className="intel-panel intel-table-panel"><header><div><span>Última actividad</span><h2>Solicitudes recientes</h2></div><small>IP anonimizada</small></header><div className="intel-table"><div className="intel-table-head"><span>Hora</span><span>Ruta</span><span>Estado</span><span>Clase</span></div>{(data?.recent||[]).map((row,index)=><div className="intel-table-row" key={`${row.time}-${index}`}><span>{new Date(row.time).toLocaleTimeString('es-AR',{hour:'2-digit',minute:'2-digit'})}</span><span title={row.path}><b>{row.method}</b> {row.path}</span><span className={row.status>=400?'bad':'good'}>{row.status}</span><span>{row.class}</span></div>)}{!data?.recent?.length&&<p className="intel-empty">Aún no se registraron solicitudes.</p>}</div></article><article className="intel-panel intel-security"><header><div><span>Seguridad</span><h2>Señales recientes</h2></div><small>{metrics.security||0} eventos</small></header>{(data?.security_events||[]).map((event,index)=><div className="intel-security-row" key={`${event.time}-${index}`}><span className={`risk ${event.risk}`}>{event.risk}</span><div><strong>{event.type.replaceAll('_',' ')}</strong><small title={event.path}>{event.ip} · {event.path}</small></div></div>)}{!data?.security_events?.length&&<div className="intel-safe"><ShieldCheck size={25}/><strong>Sin señales en el período</strong><span>Esto no garantiza ausencia de riesgo.</span></div>}</article></section>
    <footer className="intel-health"><span><i className="ok"/>Base de datos: <strong>{data?.health?.database||'—'}</strong></span><span><i className={data?.health?.failed_jobs?'warn':'ok'}/>Trabajos fallidos: <strong>{data?.health?.failed_jobs??'sin dato'}</strong></span><span>Cola: <strong>{data?.health?.queue_connection||'—'}</strong></span><span>Geografía: <strong>{data?.geography?.available?'activa':'sin proveedor configurado'}</strong></span></footer>
  </div>;
}

function Cms({ pages, recommendations, flash, errors, siteSettings, users, contactInquiries, newsletter, intelligence, initialModule='home_hero', commerce = { clients:[], orders:[], invoices:[], products:[], totals:{} } }) {
  const page = pages.find((item) => item.slug === 'inicio') || pages[0];
  const [selected, setSelected] = useState(initialModule);
  const [homeOpen, setHomeOpen] = useState(true);
  const [productsOpen, setProductsOpen] = useState(true);
  const [privateOpen, setPrivateOpen] = useState(true);
  const navItems = useMemo(() => [...primaryNav.flatMap((item) => item.children ? item.children : item), ...utilityNav], []);
  const selectedNav = navItems.find((item) => item.key === selected);
  const selectedPage = selectedNav?.pageSlug ? pages.find((item) => item.slug === selectedNav.pageSlug) : page;
  const currentSection = useMemo(() => selectedNav?.section ? selectedPage?.sections.find((section) => section.type === selectedNav.section) : selectedNav?.pageSlug ? selectedPage?.sections?.[0] : null, [selectedPage, selectedNav]);

  let content;
  if (selected === 'intelligence') content = <IntelligenceDashboard data={intelligence} />;
  else if (selected === 'seo') content = <SeoModule pages={pages} />;
  else if (selected === 'users') content = <UsersModule users={users} />;
  else if (selected === 'whatsapp') content = <WhatsAppModule contact={siteSettings.contact} />;
  else if (selected === 'contact') content = <ContactModule initial={siteSettings.contact} inquiries={contactInquiries} />;
  else if (selected === 'newsletter') content = <NewsletterModule newsletter={newsletter} />;
  else if (selected === 'social') content = <SocialModule initial={siteSettings.social} />;
  else if (selected === 'private_clients') content = <ClientModule clients={commerce.clients} total={commerce.totals?.clients} />;
  else if (selected === 'private_cart') content = <OrderModule orders={commerce.orders} mode="cart" />;
  else if (selected === 'private_logistics') content = <OrderModule orders={commerce.orders} mode="logistics" />;
  else if (selected === 'private_stock') content = <StockModule products={commerce.products} />;
  else if (selected === 'private_accounting') content = <OrderModule orders={commerce.orders} mode="accounting" />;
  else if (selected === 'private_billed') content = <OrderModule orders={commerce.orders} mode="billed" />;
  else if (selected === 'private_invoices') content = <InvoiceModule invoices={commerce.invoices} />;
  else if (selected === 'private_payments') content = <PaymentModule payments={commerce.payments} />;
  else if (selected === 'private_config') content = <SettingsModule type="client_portal" initial={siteSettings.client_portal} />;
  else if (selected === 'private_all_orders') content = <OrderModule orders={commerce.orders} mode="all" />;
  else if (selected === 'private_exports') content = <ExportModule commerce={commerce} />;
  else if (selected === 'stores') content = <StoresModule initial={siteSettings.stores} />;
  else if (currentSection) content = <>{selectedNav?.pageSlug && ['categorias', 'productos', 'novedades'].includes(selectedNav.pageSlug) && <div className="module-content presence-wrap"><PagePresenceSwitch page={selectedPage} /></div>}<SectionEditor key={currentSection.id} section={currentSection} recommendations={recommendations} /></>;
  else content = <div className="empty-state"><h2>Módulo no configurado</h2><p>Esta sección todavía no existe.</p></div>;

  return <div className="admin-shell elevated-shell"><AppleNotification flash={flash} errors={errors} /><aside className="admin-sidebar premium-sidebar"><div className="sidebar-brand"><img src="/assets/figma/exact/logo-header.png" alt="Moldpack" /><div><strong>Moldpack</strong><span>Content Studio</span></div></div><nav><p className="nav-label">Inteligencia</p><a href="/dashboard" className={`admin-nav-link ${selected==='intelligence'?'active':''}`}><ChartNoAxesCombined size={20}/><span>Vista general</span><ChevronRight size={15}/></a><p className="nav-label">Sitio web</p>{primaryNav.map(({ key, label, icon: Icon, children }) => { const isHomeParent = key === 'home'; const isProductsParent = key === 'products_parent'; const isPrivateParent = key === 'private'; const isOpen = isHomeParent ? homeOpen : isProductsParent ? productsOpen : isPrivateParent ? privateOpen : false; const childActive = !!children?.some((child) => child.key === selected); return <React.Fragment key={key}>{isPrivateParent&&<p className="nav-label utility">Operaciones</p>}<button type="button" className={childActive ? 'active parent-open' : selected === key ? 'active' : ''} onClick={() => children ? (isHomeParent ? setHomeOpen(!homeOpen) : isProductsParent ? setProductsOpen(!productsOpen) : setPrivateOpen(!privateOpen)) : setSelected(key)}><Icon size={19} /><span>{label}</span>{children ? (isOpen ? <ChevronDown size={15} /> : <ChevronRight size={15} />) : <ChevronRight size={15} />}</button>{children && isOpen && <div className="home-subnav">{children.map(({ key: childKey, label: childLabel, icon: ChildIcon }) => <button key={childKey} type="button" className={selected === childKey ? 'active' : ''} onClick={() => setSelected(childKey)}><ChildIcon size={16} /><span>{childLabel}</span></button>)}</div>}</React.Fragment>; })}<p className="nav-label utility">Módulos</p>{utilityNav.map(({ key, label, icon: Icon }) => <button key={key} type="button" className={selected === key ? 'active' : ''} onClick={() => setSelected(key)}><Icon size={19} /><span>{label}</span><ChevronRight size={15} /></button>)}</nav><div className="sidebar-footer"><a href="/" target="_blank" rel="noreferrer"><Eye size={18} />Ver sitio público</a><form method="post" action="/admin/logout"><input type="hidden" name="_token" value={csrf} /><button type="submit"><LogOut size={18} />Cerrar sesión</button></form></div></aside><main className="admin-main premium-main"><div className="mobile-topbar"><LayoutDashboard size={20} /><strong>CMS Moldpack</strong></div>{content}</main></div>;
}

createInertiaApp({
  progress: false,
  resolve: (name) => ({ 'Admin/Cms': Cms })[name],
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
