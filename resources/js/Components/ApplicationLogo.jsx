import logo from '../../assets/images/logo.png';
export default function ApplicationLogo(props) {
    return (
        <img {...props} src={logo} viewBox="0 0 316 316" alt="Logo Company" />
    );
}
